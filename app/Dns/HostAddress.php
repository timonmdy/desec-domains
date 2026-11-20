<?php

namespace Pterodactyl\BlueprintFramework\Extensions\desec\Dns;

use Pterodactyl\Models\Server;

final class HostAddress
{
    /** Public IP players should reach: the allocation alias if it is an IP, otherwise the allocation IP. */
    public static function publicIp(Server $server): ?string
    {
        $server->loadMissing('allocation');
        foreach ([$server->allocation?->ip_alias, $server->allocation?->ip] as $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate === '') {
                continue;
            }

            return self::isPublic($candidate) ? $candidate : null;
        }

        return null;
    }

    public static function type(string $ip): ?string
    {
        return match (true) {
            (bool) filter_var($ip, FILTER_VALIDATE_IPV4) => 'A',
            (bool) filter_var($ip, FILTER_VALIDATE_IPV6) => 'AAAA',
            default => null,
        };
    }

    private static function isPublic(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }
}
