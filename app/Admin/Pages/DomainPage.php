<?php

namespace Pterodactyl\BlueprintFramework\Extensions\desec\Admin\Pages;

use Illuminate\Http\Request;
use Pterodactyl\BlueprintFramework\Extensions\desec\Services\DesecException;
use Pterodactyl\BlueprintFramework\Extensions\desec\Services\DomainService;
use Pterodactyl\BlueprintFramework\Extensions\desec\Services\RecordService;
use Pterodactyl\BlueprintFramework\Extensions\desec\Storage\AllocationStore;
use Pterodactyl\BlueprintFramework\Extensions\desec\Storage\DomainStore;

class DomainPage implements Page
{
    public function __construct(
        private DomainStore $domains,
        private AllocationStore $allocations,
        private DomainService $service,
        private RecordService $records,
    ) {
    }

    public function name(): string
    {
        return 'domain';
    }

    public function data(Request $request): array
    {
        $domain = $this->domains->find((int) $request->query('id')) ?? abort(404, 'Unknown domain.');
        [$records, $recordError] = $this->loadRecords($domain);

        return [
            'domain' => DomainStore::view($domain),
            'status' => $this->service->status($domain),
            'allocations' => $this->allocations->forDomain($domain['id']),
            'records' => $records,
            'recordError' => $recordError,
            'recordTypes' => RecordService::TYPES,
            'edit' => $this->editing($request, $records),
        ];
    }

    private function loadRecords(array $domain): array
    {
        try {
            return [$this->records->list($domain), null];
        } catch (DesecException $exception) {
            return [[], $exception->getMessage()];
        }
    }

    /** The record set to prefill the editor with, picked via ?edit_subname=&edit_type= (no JavaScript needed). */
    private function editing(Request $request, array $records): ?array
    {
        foreach ($records as $record) {
            if ($record['subname'] === (string) $request->query('edit_subname') && $record['type'] === (string) $request->query('edit_type')) {
                return $record;
            }
        }

        return null;
    }
}
