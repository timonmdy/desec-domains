<?php

namespace Pterodactyl\BlueprintFramework\Extensions\desec\Services;

use Pterodactyl\BlueprintFramework\Extensions\desec\Support\SecretBox;
use Pterodactyl\BlueprintFramework\Extensions\desec\Support\Settings;

class TokenResolver
{
    public function __construct(private Settings $settings)
    {
    }

    /** A per-domain token wins over the default token from the extension settings. */
    public function forDomain(array $domain): string
    {
        $own = SecretBox::open((string) ($domain['sealed_token'] ?? ''));

        return $this->require($own !== '' ? $own : $this->settings->apiToken());
    }

    public function default(): string
    {
        return $this->require($this->settings->apiToken());
    }

    private function require(string $token): string
    {
        if ($token === '') {
            throw new DesecException('No deSEC API token is configured. Add one in the extension settings or on the domain.');
        }

        return $token;
    }
}
