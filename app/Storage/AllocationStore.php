<?php

namespace Pterodactyl\BlueprintFramework\Extensions\desec\Storage;

use Illuminate\Support\Facades\DB;

class AllocationStore
{
    private const TABLE = 'desec_allocations';

    public function all(): array
    {
        return $this->query()->orderBy('a.id')->get()->map(fn ($row) => $this->dto($row))->all();
    }

    public function forServer(int $serverId): array
    {
        return $this->query()->where('a.server_id', $serverId)->orderBy('d.name')->orderBy('a.subdomain')
            ->get()->map(fn ($row) => $this->dto($row))->all();
    }

    public function forDomain(int $domainId): array
    {
        return $this->query()->where('a.domain_id', $domainId)->orderBy('a.subdomain')
            ->get()->map(fn ($row) => $this->dto($row))->all();
    }

    public function find(int $id): ?array
    {
        $row = $this->query()->where('a.id', $id)->first();

        return $row ? $this->dto($row) : null;
    }

    public function exists(int $domainId, string $subdomain): bool
    {
        return DB::table(self::TABLE)->where('domain_id', $domainId)->where('subdomain', $subdomain)->exists();
    }

    public function countForDomain(int $domainId): int
    {
        return DB::table(self::TABLE)->where('domain_id', $domainId)->count();
    }

    public function create(array $data): int
    {
        return (int) DB::table(self::TABLE)->insertGetId($this->row($data) + [
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function update(int $id, array $data): void
    {
        DB::table(self::TABLE)->where('id', $id)->update($this->row($data) + ['updated_at' => now()]);
    }

    public function delete(int $id): void
    {
        DB::table(self::TABLE)->where('id', $id)->delete();
    }

    private function query()
    {
        return DB::table(self::TABLE . ' as a')
            ->join('desec_domains as d', 'd.id', '=', 'a.domain_id')
            ->select('a.*', 'd.name as domain_name');
    }

    private function row(array $data): array
    {
        $row = array_intersect_key($data, array_flip([
            'server_id', 'domain_id', 'subdomain', 'target', 'port', 'address', 'status', 'last_error', 'created_by',
        ]));
        if (array_key_exists('records', $data)) {
            $row['records'] = json_encode($data['records']);
        }
        if (array_key_exists('notes', $data)) {
            $row['notes'] = json_encode($data['notes']);
        }

        return $row;
    }

    private function dto(object $row): array
    {
        $subdomain = (string) $row->subdomain;
        $records = json_decode((string) $row->records, true);
        $notes = json_decode((string) ($row->notes ?? '[]'), true);

        return [
            'id' => (int) $row->id,
            'server_id' => (int) $row->server_id,
            'domain_id' => (int) $row->domain_id,
            'domain' => (string) $row->domain_name,
            'subdomain' => $subdomain,
            'host' => $subdomain !== '' ? $subdomain . '.' . $row->domain_name : (string) $row->domain_name,
            'kind' => $subdomain !== '' ? 'subdomain' : 'domain',
            'target' => (string) $row->target,
            'port' => (int) $row->port,
            'address' => $row->address !== null ? (string) $row->address : null,
            'records' => is_array($records) ? $records : [],
            'notes' => is_array($notes) ? $notes : [],
            'status' => (string) $row->status,
            'last_error' => $row->last_error !== null ? (string) $row->last_error : null,
            'updated_at' => (int) strtotime((string) $row->updated_at),
        ];
    }
}
