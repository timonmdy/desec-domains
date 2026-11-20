<?php

namespace Pterodactyl\BlueprintFramework\Extensions\desec\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Pterodactyl\BlueprintFramework\Extensions\desec\Support\DebugLog;
use Pterodactyl\BlueprintFramework\Extensions\desec\Support\Settings;

/**
 * Thin wrapper around the deSEC REST API. Holds no state and never logs the token.
 * Callers pass the token per call because every domain may use its own.
 */
class DesecClient
{
    private const MAX_PAGES = 20;

    public function __construct(private Settings $settings)
    {
    }

    /** Domains visible to the token (first page, deSEC returns up to 500). */
    public function domains(string $token): array
    {
        return $this->send('GET', '/domains/', $token)->json() ?? [];
    }

    public function domain(string $token, string $name): array
    {
        return $this->send('GET', '/domains/' . rawurlencode($name) . '/', $token)->json() ?? [];
    }

    /** All RRsets of a zone, following deSEC's cursor pagination. */
    public function rrsets(string $token, string $domain): array
    {
        $all = [];
        $cursor = null;
        for ($page = 0; $page < self::MAX_PAGES; $page++) {
            $response = $this->send('GET', $this->rrsetPath($domain), $token, null, $cursor !== null ? ['cursor' => $cursor] : []);
            $all = array_merge($all, $response->json() ?? []);
            $cursor = $this->nextCursor($response);
            if ($cursor === null) {
                break;
            }
        }

        return $all;
    }

    public function rrset(string $token, string $domain, string $subname, string $type): ?array
    {
        try {
            return $this->send('GET', $this->rrsetPath($domain, $subname, $type), $token)->json();
        } catch (DesecException $exception) {
            if ($exception->status() === 404) {
                return null;
            }

            throw $exception;
        }
    }

    /** Bulk create. Atomic at deSEC: either every RRset is created or none. Fails if one already exists. */
    public function create(string $token, string $domain, array $rrsets): void
    {
        $this->send('POST', $this->rrsetPath($domain), $token, $rrsets);
    }

    /** Bulk create-or-replace. Atomic at deSEC. */
    public function upsert(string $token, string $domain, array $rrsets): void
    {
        $this->send('PUT', $this->rrsetPath($domain), $token, $rrsets);
    }

    /** Idempotent: deSEC answers 204 whether or not the RRset existed. */
    public function delete(string $token, string $domain, string $subname, string $type): void
    {
        $this->send('DELETE', $this->rrsetPath($domain, $subname, $type), $token);
    }

    private function send(string $method, string $path, string $token, ?array $json = null, array $query = []): Response
    {
        DebugLog::write($this->settings->all(), 'deSEC request.', ['method' => $method, 'path' => $path, 'items' => $json !== null ? count($json) : 0]);

        try {
            $response = $this->http($token)->send($method, ltrim($path, '/'), array_filter([
                'query' => $query ?: null,
                'json' => $json,
            ], fn ($value) => $value !== null));
        } catch (ConnectionException $exception) {
            throw new DesecException('Could not reach deSEC: ' . $exception->getMessage());
        }

        DebugLog::write($this->settings->all(), 'deSEC response.', ['method' => $method, 'path' => $path, 'status' => $response->status()]);

        if ($response->failed()) {
            throw DesecException::fromResponse($response);
        }

        return $response;
    }

    private function http(string $token): PendingRequest
    {
        return Http::baseUrl(rtrim((string) $this->settings->get('apiUrl'), '/'))
            ->withToken($token, 'Token')
            ->acceptJson()
            ->timeout((int) $this->settings->get('timeoutSeconds'));
    }

    /** deSEC addresses the apex as "@" in URLs. */
    private function rrsetPath(string $domain, ?string $subname = null, ?string $type = null): string
    {
        $path = '/domains/' . rawurlencode($domain) . '/rrsets/';

        return $type === null ? $path : $path . rawurlencode($subname === '' ? '@' : (string) $subname) . '/' . rawurlencode($type) . '/';
    }

    private function nextCursor(Response $response): ?string
    {
        if (!preg_match('/<([^>]+)>;\s*rel="next"/', (string) $response->header('Link'), $match)) {
            return null;
        }
        parse_str((string) parse_url($match[1], PHP_URL_QUERY), $query);

        return isset($query['cursor']) && is_string($query['cursor']) ? $query['cursor'] : null;
    }
}
