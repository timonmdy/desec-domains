<?php

namespace Pterodactyl\BlueprintFramework\Extensions\desec\Storage;

use Illuminate\Support\Facades\DB;

class DomainStore
{
    private const TABLE = 'desec_domains';

    public function all(): array
    {
        return DB::table(self::TABLE)->orderBy('name')->get()->map(fn ($row) => $this->dto($row))->all();
    }

    public function find(int $id): ?array
    {
        $row = DB::table(self::TABLE)->where('id', $id)->first();

        return $row ? $this->dto($row) : null;
    }

    public function findByName(string $name): ?array
    {
        $row = DB::table(self::TABLE)->where('name', $name)->first();

        return $row ? $this->dto($row) : null;
    }

    public function create(string $name, string $sealedToken, ?int $userId): int
    {
        return (int) DB::table(self::TABLE)->insertGetId([
            'name' => $name,
            'token' => $sealedToken !== '' ? $sealedToken : null,
            'created_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function setToken(int $id, string $sealedToken): void
    {
        DB::table(self::TABLE)->where('id', $id)->update([
            'token' => $sealedToken !== '' ? $sealedToken : null,
            'updated_at' => now(),
        ]);
    }

    public function delete(int $id): void
    {
        DB::table(self::TABLE)->where('id', $id)->delete();
    }

    /** Same row without the sealed token, safe for views and API responses. */
    public static function view(array $domain): array
    {
        unset($domain['sealed_token']);

        return $domain;
    }

    private function dto(object $row): array
    {
        return [
            'id' => (int) $row->id,
            'name' => (string) $row->name,
            'sealed_token' => (string) ($row->token ?? ''),
            'has_token' => (string) ($row->token ?? '') !== '',
            'created_at' => (string) $row->created_at,
        ];
    }
}
