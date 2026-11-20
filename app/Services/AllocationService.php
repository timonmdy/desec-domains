<?php

namespace Pterodactyl\BlueprintFramework\Extensions\desec\Services;

use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Pterodactyl\Models\Server;
use RuntimeException;
use Throwable;
use Pterodactyl\BlueprintFramework\Extensions\desec\Dns\AllocationPlan;
use Pterodactyl\BlueprintFramework\Extensions\desec\Dns\HostAddress;
use Pterodactyl\BlueprintFramework\Extensions\desec\Storage\AllocationStore;
use Pterodactyl\BlueprintFramework\Extensions\desec\Storage\DomainStore;
use Pterodactyl\BlueprintFramework\Extensions\desec\Support\DomainName;

/**
 * Owns the lifecycle of one SRV allocation: create at deSEC, remember what we own, remove only that again.
 * Records that already existed (for example an A record on the apex) are never touched.
 */
class AllocationService
{
    public function __construct(
        private DesecClient $client,
        private TokenResolver $tokens,
        private DomainService $domains,
        private DomainStore $domainStore,
        private AllocationStore $allocations,
        private AllocationPlan $plan,
    ) {
    }

    public function allocate(Server $server, array $domain, string $subdomain, bool $wildcard, ?string $target, ?int $userId): int
    {
        $subdomain = DomainName::normalize($subdomain);
        if ($subdomain !== '' && !DomainName::isSubdomain($subdomain)) {
            throw new InvalidArgumentException('Invalid subdomain. Use letters, digits and hyphens, optionally separated by dots.');
        }
        if ($this->allocations->exists($domain['id'], $subdomain)) {
            throw new InvalidArgumentException('This ' . ($subdomain === '' ? 'domain' : 'subdomain') . ' is already allocated.');
        }

        $port = $this->port($server);
        $target = $this->plan->target($domain['name'], $subdomain, $target);
        if (!DomainName::isDomain($target)) {
            throw new InvalidArgumentException("The SRV target \"{$target}\" is not a valid host name.");
        }

        $token = $this->tokens->forDomain($domain);
        $ip = HostAddress::publicIp($server);
        [$address, $notes] = $this->addressPlan($token, $domain, $target, $ip);
        $rrsets = array_merge($this->plan->srvRrsets($subdomain, $wildcard && $subdomain === '', $port, $target), $address ? [$address] : []);

        $this->client->create($token, $domain['name'], $rrsets);
        try {
            $id = $this->allocations->create([
                'server_id' => $server->id, 'domain_id' => $domain['id'], 'subdomain' => $subdomain,
                'target' => $target, 'port' => $port, 'address' => $address ? $ip : null,
                'records' => $this->refs($rrsets), 'notes' => $notes, 'status' => 'active', 'created_by' => $userId,
            ]);
        } catch (Throwable $exception) {
            $this->removeQuietly($token, $domain['name'], $rrsets);

            throw $exception;
        }

        $this->domains->forget($domain);
        Log::notice('deSEC Domains: allocation created.', ['server_id' => $server->id, 'domain' => $domain['name'], 'subdomain' => $subdomain, 'user_id' => $userId]);

        return $id;
    }

    public function release(array $allocation, ?int $userId = null): void
    {
        $domain = $this->domainStore->find($allocation['domain_id']);
        if ($domain !== null) {
            $token = $this->tokens->forDomain($domain);
            foreach ($allocation['records'] as $record) {
                if (!empty($record['owned'])) {
                    $this->client->delete($token, $domain['name'], $record['subname'], $record['type']);
                }
            }
            $this->domains->forget($domain);
        }

        $this->allocations->delete($allocation['id']);
        Log::notice('deSEC Domains: allocation released.', ['server_id' => $allocation['server_id'], 'host' => $allocation['host'], 'user_id' => $userId]);
    }

    /** Pushes the server's current port and IP to deSEC. Used by the reconcile job. */
    public function refresh(array $allocation, Server $server): void
    {
        $domain = $this->domainStore->find($allocation['domain_id']) ?? throw new RuntimeException('The domain is no longer connected.');
        $token = $this->tokens->forDomain($domain);
        $port = $this->port($server);
        $ip = HostAddress::publicIp($server);
        $records = $allocation['records'];
        $wildcard = in_array('*', array_column($records, 'subname'), true);

        $rrsets = $this->plan->srvRrsets($allocation['subdomain'], $wildcard, $port, $allocation['target']);
        $address = $this->refreshedAddress($token, $domain, $allocation['target'], $ip, $records);
        $this->client->upsert($token, $domain['name'], array_merge($rrsets, $address ? [$address] : []));

        $this->allocations->update($allocation['id'], [
            'port' => $port, 'address' => $address ? $ip : $allocation['address'], 'records' => $records,
            'status' => 'active', 'last_error' => null,
        ]);
        $this->domains->forget($domain);
    }

    public function markFailed(int $id, string $message): void
    {
        $this->allocations->update($id, ['status' => 'error', 'last_error' => mb_substr($message, 0, 500)]);
    }

    private function port(Server $server): int
    {
        $server->loadMissing('allocation');
        $port = (int) ($server->allocation?->port ?? 0);
        if ($port < 1) {
            throw new RuntimeException('This server has no main allocation to point to.');
        }

        return $port;
    }

    /** @return array{0:?array,1:list<string>} the address RRset to create, plus notes for the admin. */
    private function addressPlan(string $token, array $domain, string $target, ?string $ip): array
    {
        $spec = $this->plan->addressRrset($domain['name'], $target, $ip);
        if ($spec === null) {
            return [null, $this->addressNote($domain, $target, $ip)];
        }

        $existing = $this->client->rrset($token, $domain['name'], $spec['subname'], $spec['type']);
        if ($existing === null) {
            return [$spec, []];
        }
        if (in_array($ip, (array) ($existing['records'] ?? []), true)) {
            return [null, []];
        }

        return [null, ["An existing {$spec['type']} record for {$target} points elsewhere and was left untouched. Players follow the SRV record to that address."]];
    }

    private function addressNote(array $domain, string $target, ?string $ip): array
    {
        if (!DomainName::isWithin($target, $domain['name'])) {
            return [];
        }

        return $ip === null
            ? ["No public IP address is set on the server's allocation, so no address record was created for {$target}. Create one manually."]
            : [];
    }

    /** Keeps an owned A/AAAA record in step with the allocation IP. Replaces it when the IP family changes. */
    private function refreshedAddress(string $token, array $domain, string $target, ?string $ip, array &$records): ?array
    {
        $index = null;
        foreach ($records as $i => $record) {
            if (!empty($record['owned']) && in_array($record['type'], ['A', 'AAAA'], true)) {
                $index = $i;
            }
        }
        $spec = $index === null ? null : $this->plan->addressRrset($domain['name'], $target, $ip);
        if ($spec === null) {
            return null;
        }
        if ($records[$index]['type'] !== $spec['type']) {
            $this->client->delete($token, $domain['name'], $records[$index]['subname'], $records[$index]['type']);
            $records[$index] = ['subname' => $spec['subname'], 'type' => $spec['type'], 'owned' => true];
        }

        return $spec;
    }

    private function refs(array $rrsets): array
    {
        return array_map(fn (array $r) => ['subname' => $r['subname'], 'type' => $r['type'], 'owned' => true], $rrsets);
    }

    private function removeQuietly(string $token, string $domain, array $rrsets): void
    {
        foreach ($rrsets as $rrset) {
            try {
                $this->client->delete($token, $domain, $rrset['subname'], $rrset['type']);
            } catch (Throwable) {
                // Best effort: the original error is the one worth reporting.
            }
        }
    }
}
