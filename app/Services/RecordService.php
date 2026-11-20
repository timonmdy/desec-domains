<?php

namespace Pterodactyl\BlueprintFramework\Extensions\desec\Services;

use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Pterodactyl\BlueprintFramework\Extensions\desec\Storage\AllocationStore;
use Pterodactyl\BlueprintFramework\Extensions\desec\Support\DomainName;

/** Basic zone editor. Records created by an allocation stay read-only here and are changed from the server tab. */
class RecordService
{
    public const TYPES = ['A', 'AAAA', 'CNAME', 'MX', 'TXT', 'SRV', 'CAA', 'NS', 'TLSA', 'SSHFP', 'HTTPS', 'SVCB', 'PTR', 'DS'];

    public function __construct(
        private DesecClient $client,
        private TokenResolver $tokens,
        private DomainService $domains,
        private AllocationStore $allocations,
    ) {
    }

    /** @return list<array> RRsets with a "managed" flag. Reads are cached to protect deSEC's daily request budget. */
    public function list(array $domain): array
    {
        $managed = $this->managedKeys($domain);
        $rrsets = $this->domains->remember(
            DomainService::key('rrsets', $domain),
            fn () => $this->client->rrsets($this->tokens->forDomain($domain), $domain['name']),
        );

        return array_map(fn (array $rrset) => [
            'subname' => (string) $rrset['subname'],
            'type' => (string) $rrset['type'],
            'ttl' => (int) $rrset['ttl'],
            'records' => (array) $rrset['records'],
            'managed' => isset($managed[$this->id($rrset['subname'], $rrset['type'])]),
        ], $rrsets);
    }

    public function save(array $domain, string $subname, string $type, int $ttl, string $recordsText, ?int $userId): void
    {
        $subname = $this->subname($subname);
        $type = strtoupper(trim($type));
        $records = array_values(array_filter(array_map('trim', preg_split('/\R/', $recordsText) ?: []), fn ($line) => $line !== ''));

        if (!in_array($type, self::TYPES, true)) {
            throw new InvalidArgumentException('Unsupported record type.');
        }
        if ($records === []) {
            throw new InvalidArgumentException('Enter at least one record value, or delete the record set.');
        }
        $this->assertEditable($domain, $subname, $type);

        $this->client->upsert($this->tokens->forDomain($domain), $domain['name'], [[
            'subname' => $subname, 'type' => $type, 'ttl' => $ttl, 'records' => $records,
        ]]);
        $this->domains->forget($domain);
        Log::notice('deSEC Domains: record set saved.', ['domain' => $domain['name'], 'subname' => $subname, 'type' => $type, 'user_id' => $userId]);
    }

    public function delete(array $domain, string $subname, string $type, ?int $userId): void
    {
        $subname = $this->subname($subname);
        $type = strtoupper(trim($type));
        $this->assertEditable($domain, $subname, $type);

        $this->client->delete($this->tokens->forDomain($domain), $domain['name'], $subname, $type);
        $this->domains->forget($domain);
        Log::notice('deSEC Domains: record set deleted.', ['domain' => $domain['name'], 'subname' => $subname, 'type' => $type, 'user_id' => $userId]);
    }

    private function subname(string $value): string
    {
        $value = strtolower(trim($value));
        $value = $value === '@' ? '' : $value;
        if (!DomainName::isRecordName($value)) {
            throw new InvalidArgumentException('Invalid record name. Use letters, digits, hyphens and underscores; "*" only as the first label.');
        }

        return $value;
    }

    private function assertEditable(array $domain, string $subname, string $type): void
    {
        if (isset($this->managedKeys($domain)[$this->id($subname, $type)])) {
            throw new InvalidArgumentException('This record set belongs to a server allocation. Release the allocation to remove it.');
        }
    }

    /** @return array<string,true> */
    private function managedKeys(array $domain): array
    {
        $keys = [];
        foreach ($this->allocations->forDomain($domain['id']) as $allocation) {
            foreach ($allocation['records'] as $record) {
                if (!empty($record['owned'])) {
                    $keys[$this->id($record['subname'], $record['type'])] = true;
                }
            }
        }

        return $keys;
    }

    private function id(string $subname, string $type): string
    {
        return $subname . '|' . $type;
    }
}
