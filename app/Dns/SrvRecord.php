<?php

namespace Pterodactyl\BlueprintFramework\Extensions\desec\Dns;

use Pterodactyl\BlueprintFramework\Extensions\desec\Support\DomainName;

final class SrvRecord
{
    /** RDATA as deSEC expects it: "priority weight port target." with an absolute target. */
    public static function rdata(int $priority, int $weight, int $port, string $target): string
    {
        return sprintf('%d %d %d %s', $priority, $weight, $port, DomainName::fqdn($target));
    }
}
