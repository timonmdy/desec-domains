<?php

namespace Pterodactyl\BlueprintFramework\Extensions\desec\Dns;

use Pterodactyl\BlueprintFramework\Extensions\desec\Support\DomainName;
use Pterodactyl\BlueprintFramework\Extensions\desec\Support\Settings;

/**
 * Turns "this server, this (sub)domain, this port" into the RRsets that have to exist at deSEC.
 * Pure: builds data only and performs no I/O.
 */
class AllocationPlan
{
    public function __construct(private Settings $settings)
    {
    }

    public function target(string $domain, string $subdomain, ?string $override): string
    {
        return $override !== null && trim($override) !== ''
            ? DomainName::normalize($override)
            : TargetTemplate::render((string) $this->settings->get('srvTarget'), $subdomain, $domain);
    }

    /** @return list<string> subnames relative to the zone, e.g. _minecraft._tcp.survival or *. */
    public function srvSubnames(string $subdomain, bool $wildcard): array
    {
        $service = (string) $this->settings->get('srvSubname');
        if ($subdomain !== '') {
            return [$service . '.' . $subdomain];
        }

        return $wildcard ? [$service, '*'] : [$service];
    }

    /** @return list<array{subname:string,type:string,ttl:int,records:list<string>}> */
    public function srvRrsets(string $subdomain, bool $wildcard, int $port, string $target): array
    {
        $record = SrvRecord::rdata(
            (int) $this->settings->get('srvPriority'),
            (int) $this->settings->get('srvWeight'),
            $port,
            $target,
        );

        return array_map(fn (string $subname) => [
            'subname' => $subname,
            'type' => 'SRV',
            'ttl' => (int) $this->settings->get('srvTtl'),
            'records' => [$record],
        ], $this->srvSubnames($subdomain, $wildcard));
    }

    /** @return array{subname:string,type:string,ttl:int,records:list<string>}|null */
    public function addressRrset(string $domain, string $target, ?string $ip): ?array
    {
        if (!(bool) $this->settings->get('manageAddressRecords') || $ip === null || !DomainName::isWithin($target, $domain)) {
            return null;
        }

        $type = HostAddress::type($ip);
        if ($type === null) {
            return null;
        }

        return [
            'subname' => DomainName::relative($target, $domain),
            'type' => $type,
            'ttl' => (int) $this->settings->get('addressTtl'),
            'records' => [$ip],
        ];
    }
}
