<?php

namespace Pterodactyl\BlueprintFramework\Extensions\desec\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Pterodactyl\BlueprintFramework\Extensions\desec\Storage\AllocationStore;
use Pterodactyl\BlueprintFramework\Extensions\desec\Storage\DomainStore;
use Pterodactyl\BlueprintFramework\Extensions\desec\Support\DomainName;
use Pterodactyl\BlueprintFramework\Extensions\desec\Support\SecretBox;
use Pterodactyl\BlueprintFramework\Extensions\desec\Support\Settings;

class DomainService
{
    public function __construct(
        private DesecClient $client,
        private TokenResolver $tokens,
        private DomainStore $domains,
        private AllocationStore $allocations,
        private Settings $settings,
    ) {
    }

    /** Verifies the domain against deSEC before storing it, so a typo or a wrong token fails here. */
    public function connect(string $name, string $token, ?int $userId): int
    {
        $name = DomainName::normalize($name);
        if (!DomainName::isDomain($name)) {
            throw new InvalidArgumentException('Enter a valid domain such as example.com.');
        }
        if ($this->domains->findByName($name) !== null) {
            throw new InvalidArgumentException("{$name} is already connected.");
        }

        $token = trim($token);
        $this->client->domain($token !== '' ? $token : $this->tokens->default(), $name);
        $id = $this->domains->create($name, SecretBox::seal($token), $userId);
        Log::notice('deSEC Domains: domain connected.', ['domain' => $name, 'user_id' => $userId]);

        return $id;
    }

    public function disconnect(array $domain, ?int $userId): void
    {
        if ($this->allocations->countForDomain($domain['id']) > 0) {
            throw new InvalidArgumentException('Release every server allocation on this domain first.');
        }

        $this->domains->delete($domain['id']);
        $this->forget($domain);
        Log::notice('deSEC Domains: domain disconnected.', ['domain' => $domain['name'], 'user_id' => $userId]);
    }

    /** An empty token removes the domain-specific token so the default token applies again. */
    public function setToken(array $domain, string $token, ?int $userId): void
    {
        $token = trim($token);
        $effective = $token !== '' ? $token : $this->tokens->default();
        $this->client->domain($effective, $domain['name']);
        $this->domains->setToken($domain['id'], SecretBox::seal($token));
        $this->forget($domain);
        Log::notice('deSEC Domains: domain token changed.', ['domain' => $domain['name'], 'user_id' => $userId, 'cleared' => $token === '']);
    }

    /** @return array{ok:bool,error:?string,info:array} A broken token on one domain must not break the overview. */
    public function status(array $domain): array
    {
        try {
            $info = $this->remember(self::key('domain', $domain), fn () => $this->client->domain($this->tokens->forDomain($domain), $domain['name']));

            return ['ok' => true, 'error' => null, 'info' => $info];
        } catch (DesecException $exception) {
            return ['ok' => false, 'error' => $exception->getMessage(), 'info' => []];
        }
    }

    /** Zones the default token can see that are not connected yet. Empty when the lookup fails. */
    public function discoverable(): array
    {
        try {
            $connected = array_column($this->domains->all(), 'name');
            $names = array_column($this->remember('desec:discover', fn () => $this->client->domains($this->tokens->default())), 'name');

            return array_values(array_diff($names, $connected));
        } catch (DesecException) {
            return [];
        }
    }

    public function forget(array $domain): void
    {
        Cache::forget(self::key('domain', $domain));
        Cache::forget(self::key('rrsets', $domain));
        Cache::forget('desec:discover');
    }

    public static function key(string $kind, array $domain): string
    {
        return 'desec:' . $kind . ':' . $domain['id'];
    }

    public function remember(string $key, \Closure $load): mixed
    {
        $ttl = (int) $this->settings->get('cacheSeconds');

        return $ttl > 0 ? Cache::remember($key, $ttl, $load) : $load();
    }
}
