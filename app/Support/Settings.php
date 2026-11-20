<?php

namespace Pterodactyl\BlueprintFramework\Extensions\desec\Support;

use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class Settings
{
    private const KEY = 'desec::config';

    private ?array $cache = null;

    public function __construct(private SettingsRepositoryInterface $store)
    {
    }

    public function all(): array
    {
        if ($this->cache === null) {
            $stored = json_decode((string) $this->store->get(self::KEY, '{}'), true);
            $this->cache = array_merge(SettingsSchema::defaults(), is_array($stored) ? $stored : []);
        }

        return $this->cache;
    }

    public function get(string $key): mixed
    {
        return $this->all()[$key] ?? null;
    }

    /** Plain-text default token. Never hand this to a view or an API response. */
    public function apiToken(): string
    {
        return SecretBox::open((string) $this->get('apiToken'));
    }

    /** Values safe to render in the admin form: secrets are replaced by an empty string. */
    public function formValues(): array
    {
        $values = $this->all();
        foreach (SettingsSchema::fields() as $key => $field) {
            if ($field['type'] === 'secret') {
                $values[$key] = '';
            }
        }

        return $values;
    }

    public function secretIsSet(string $key): bool
    {
        return SecretBox::open((string) $this->get($key)) !== '';
    }

    public function save(array $input): void
    {
        $clean = [];
        foreach (SettingsSchema::fields() as $key => $field) {
            $clean[$key] = $field['type'] === 'secret'
                ? $this->secret($key, $input)
                : $this->coerce($field, $input[$key] ?? null);
        }

        $this->store->set(self::KEY, json_encode($clean));
        $this->cache = null;
    }

    public function publicConfig(): array
    {
        return [
            'debugLogging' => (bool) $this->get('debugLogging'),
        ];
    }

    private function secret(string $key, array $input): string
    {
        if (filter_var($input[$key . '_clear'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return '';
        }

        $typed = trim((string) ($input[$key] ?? ''));

        return $typed !== '' ? SecretBox::seal($typed) : (string) $this->get($key);
    }

    private function coerce(array $field, mixed $value): mixed
    {
        return match ($field['type']) {
            'int' => max($field['min'], min($field['max'], (int) ($value ?? $field['default']))),
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'select' => array_key_exists((string) $value, $field['options']) ? (string) $value : $field['default'],
            default => trim((string) ($value ?? $field['default'])),
        };
    }
}
