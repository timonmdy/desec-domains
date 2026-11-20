<?php

namespace Pterodactyl\BlueprintFramework\Extensions\desec\Support;

final class SettingsSchema
{
    public static function sections(): array
    {
        return [
            'General' => [
                'clientVisibility' => ['type' => 'select', 'default' => 'all', 'options' => ['all' => 'Everyone with access to the server', 'owner' => 'Server owner and administrators only'], 'label' => 'Who sees the domains on the console page'],
                'debugLogging' => ['type' => 'bool', 'default' => false, 'label' => 'Enable detailed debug logging', 'help' => 'Writes every deSEC request (never the token) to the panel Laravel log, and to scheduler stderr for the reconcile job. Disable after troubleshooting.'],
            ],
            'deSEC API' => [
                'apiToken' => ['type' => 'secret', 'default' => '', 'label' => 'API token', 'help' => 'Default token for every connected domain. Write-only: leave empty to keep the stored token. A token with scoping policies can be limited to specific domains; domains can also get their own token under Domains.'],
                'apiUrl' => ['type' => 'url', 'default' => 'https://desec.io/api/v1', 'label' => 'API base URL', 'help' => 'Only change this for a self-hosted deSEC instance.'],
                'timeoutSeconds' => ['type' => 'int', 'default' => 15, 'min' => 3, 'max' => 120, 'label' => 'Request timeout (seconds)'],
                'cacheSeconds' => ['type' => 'int', 'default' => 60, 'min' => 0, 'max' => 3600, 'label' => 'Cache deSEC reads (seconds)', 'help' => 'deSEC allows 2000 requests per day per account. Overview pages reuse cached reads for this long. 0 disables caching.'],
            ],
            'SRV record defaults' => [
                'srvSubname' => ['type' => 'text', 'default' => '_minecraft._tcp', 'label' => 'Service name (subname)'],
                'srvTarget' => ['type' => 'text', 'default' => '{subdomain}.{domain}', 'label' => 'Target', 'help' => 'Placeholders: {subdomain} {domain}. An empty subdomain (whole-domain allocation) collapses the dot, so the default resolves to just the domain.'],
                'srvPriority' => ['type' => 'int', 'default' => 0, 'min' => 0, 'max' => 65535, 'label' => 'Priority'],
                'srvWeight' => ['type' => 'int', 'default' => 0, 'min' => 0, 'max' => 65535, 'label' => 'Weight'],
                'srvTtl' => ['type' => 'int', 'default' => 3600, 'min' => 60, 'max' => 86400, 'label' => 'TTL (seconds)', 'help' => 'deSEC enforces a per-domain minimum TTL (usually 3600). Lower values are rejected by the API.'],
            ],
            'Address records' => [
                'manageAddressRecords' => ['type' => 'bool', 'default' => true, 'label' => 'Create A/AAAA record for the SRV target', 'help' => 'An SRV target must resolve to an address. When the target is inside the connected domain and no address record exists yet, one is created from the server allocation IP (alias first). Existing records are never modified.'],
                'addressTtl' => ['type' => 'int', 'default' => 3600, 'min' => 60, 'max' => 86400, 'label' => 'Address record TTL (seconds)'],
            ],
        ];
    }

    public static function fields(): array
    {
        return array_merge(...array_values(self::sections()));
    }

    public static function defaults(): array
    {
        return array_map(fn (array $f) => $f['default'], self::fields());
    }
}
