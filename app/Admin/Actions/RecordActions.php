<?php

namespace Pterodactyl\BlueprintFramework\Extensions\desec\Admin\Actions;

use Illuminate\Http\Request;
use Pterodactyl\BlueprintFramework\Extensions\desec\Services\RecordService;
use Pterodactyl\BlueprintFramework\Extensions\desec\Storage\AllocationStore;
use Pterodactyl\BlueprintFramework\Extensions\desec\Storage\DomainStore;

class RecordActions extends Action
{
    public function __construct(DomainStore $domains, AllocationStore $allocations, private RecordService $records)
    {
        parent::__construct($domains, $allocations);
    }

    public function save(Request $request, int $userId): string
    {
        $request->validate([
            'subname' => ['nullable', 'string', 'max:190'],
            'type' => ['required', 'string', 'max:10'],
            'ttl' => ['required', 'integer', 'min:60', 'max:604800'],
            'records' => ['required', 'string', 'max:8000'],
        ]);
        $this->records->save(
            $this->domain($request, 'domain_id'),
            (string) $request->input('subname'),
            (string) $request->input('type'),
            (int) $request->input('ttl'),
            (string) $request->input('records'),
            $userId,
        );

        return 'Record set saved.';
    }

    public function delete(Request $request, int $userId): string
    {
        $request->validate(['subname' => ['nullable', 'string', 'max:190'], 'type' => ['required', 'string', 'max:10']]);
        $this->records->delete($this->domain($request, 'domain_id'), (string) $request->input('subname'), (string) $request->input('type'), $userId);

        return 'Record set deleted.';
    }
}
