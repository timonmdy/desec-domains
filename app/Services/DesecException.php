<?php

namespace Pterodactyl\BlueprintFramework\Extensions\desec\Services;

use Illuminate\Http\Client\Response;
use RuntimeException;

class DesecException extends RuntimeException
{
    public function __construct(string $message, private int $status = 0, private ?int $retryAfter = null)
    {
        parent::__construct($message, $status);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function isRateLimited(): bool
    {
        return $this->status === 429;
    }

    public static function fromResponse(Response $response): self
    {
        $status = $response->status();
        $retry = $response->header('Retry-After');
        $retryAfter = is_numeric($retry) ? (int) $retry : null;

        $message = match (true) {
            $status === 401 => 'deSEC rejected the API token. It may be invalid or expired.',
            $status === 403 => 'The deSEC token is not allowed to do this. Check its scoping policies.',
            $status === 404 => 'deSEC does not know this domain or record.',
            $status === 429 => 'deSEC rate limit reached.' . ($retryAfter !== null ? " Try again in {$retryAfter} seconds." : ' Try again shortly.'),
            $status >= 500 => 'deSEC is currently unavailable (HTTP ' . $status . ').',
            default => 'deSEC refused the request: ' . self::describe($response->json()),
        };

        return new self($message, $status, $retryAfter);
    }

    private static function describe(mixed $body): string
    {
        if (!is_array($body)) {
            return 'unexpected response.';
        }

        $parts = [];
        array_walk_recursive($body, function ($value) use (&$parts) {
            if (is_string($value) && $value !== '') {
                $parts[] = $value;
            }
        });

        return $parts === [] ? 'no details given.' : implode(' ', array_unique($parts));
    }
}
