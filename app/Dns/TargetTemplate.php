<?php

namespace Pterodactyl\BlueprintFramework\Extensions\desec\Dns;

use Pterodactyl\BlueprintFramework\Extensions\desec\Support\DomainName;

final class TargetTemplate
{
    public static function render(string $template, string $subdomain, string $domain): string
    {
        $host = strtr($template, ['{subdomain}' => $subdomain, '{domain}' => $domain]);

        return DomainName::normalize((string) preg_replace('/\.{2,}/', '.', ltrim($host, '.')));
    }

    public static function isValid(string $template): bool
    {
        return DomainName::isDomain(self::render($template, 'sub', 'example.com'));
    }
}
