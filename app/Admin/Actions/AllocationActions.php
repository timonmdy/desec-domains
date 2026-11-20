<?php

namespace Pterodactyl\BlueprintFramework\Extensions\desec\Admin\Actions;

use Illuminate\Http\Request;
use Pterodactyl\Models\Server;
use Pterodactyl\BlueprintFramework\Extensions\desec\Services\AllocationService;
use Pterodactyl\BlueprintFramework\Extensions\desec\Storage\AllocationStore;
use Pterodactyl\BlueprintFramework\Extensions\desec\Storage\DomainStore;

class AllocationActions extends Action
{
    public function __construct(DomainStore $domains, AllocationStore $allocations, private AllocationService $service)
    {
        parent::__construct($domains, $allocations);
    }

    public function create(Request $request, int $userId): string
    {
        $request->validate([
            'server_id' => ['required', 'integer'],
            'kind' => ['required', 'in:domain,subdomain'],
            'subdomain' => ['required_if:kind,subdomain', 'nullable', 'string', 'max:120'],
            'target' => ['nullable', 'string', 'max:253'],
        ]);

        $isSubdomain = $request->input('kind') === 'subdomain';
        $server = Server::query()->with('allocation')->findOrFail((int) $request->input('server_id'));
        $this->service->allocate(
            $server,
            $this->domain($request, 'domain_id'),
            $isSubdomain ? (string) $request->input('subdomain') : '',
            !$isSubdomain && $request->boolean('wildcard'),
            (string) $request->input('target'),
            $userId,
        );

        return $isSubdomain ? 'Subdomain allocated.' : 'Domain allocated.';
    }

    public function release(Request $request, int $userId): string
    {
        $allocation = $this->allocations->find((int) $request->input('id')) ?? abort(404, 'Unknown allocation.');
        $this->service->release($allocation, $userId);

        return 'Allocation released and its DNS records removed.';
    }
}
