<?php

namespace Pterodactyl\BlueprintFramework\Extensions\desec\Support;

final class DomainName
{
    private const LABEL = '[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?';

    public static function normalize(string $value): string
    {
        return rtrim(strtolower(trim($value)), '.');
    }

    public static function fqdn(string $host): string
    {
        return rtrim($host, '.') . '.';
    }

    public static function isLabel(string $value): bool
    {
        return (bool) preg_match('/^' . self::LABEL . '$/', $value);
    }

    /** A registrable zone such as example.com: at least two valid labels. */
    public static function isDomain(string $value): bool
    {
        return strlen($value) <= 253 && self::allLabels($value, 2);
    }

    /** One or more plain host labels (no underscores, no wildcard) that sit left of a domain. */
    public static function isSubdomain(string $value): bool
    {
        return strlen($value) <= 120 && self::allLabels($value, 1);
    }

    /** Service names such as _minecraft._tcp. Labels may start with an underscore. */
    public static function isServiceName(string $value): bool
    {
        $labels = explode('.', $value);
        foreach ($labels as $label) {
            if (!preg_match('/^_?' . self::LABEL . '$/', $label)) {
                return false;
            }
        }

        return strlen($value) <= 120;
    }

    /** Record names for the domain editor: empty = apex, labels may use underscores, "*" only as the first label. */
    public static function isRecordName(string $value): bool
    {
        if ($value === '') {
            return true;
        }
        if (strlen($value) > 190) {
            return false;
        }
        foreach (explode('.', $value) as $index => $label) {
            if ($label === '*' && $index === 0) {
                continue;
            }
            if (!preg_match('/^_?' . self::LABEL . '$/', $label)) {
                return false;
            }
        }

        return true;
    }

    public static function isWithin(string $host, string $domain): bool
    {
        return $host === $domain || str_ends_with($host, '.' . $domain);
    }

    /** Name relative to the zone, using an empty string for the apex (deSEC calls this the subname). */
    public static function relative(string $host, string $domain): string
    {
        return $host === $domain ? '' : substr($host, 0, -(strlen($domain) + 1));
    }

    private static function allLabels(string $value, int $min): bool
    {
        $labels = explode('.', $value);
        if (count($labels) < $min) {
            return false;
        }
        foreach ($labels as $label) {
            if (!self::isLabel($label)) {
                return false;
            }
        }

        return true;
    }
}
