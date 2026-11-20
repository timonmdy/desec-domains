<?php

namespace Pterodactyl\BlueprintFramework\Extensions\desec\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Models\Server;
use Pterodactyl\BlueprintFramework\Extensions\desec\Storage\AllocationStore;
use Pterodactyl\BlueprintFramework\Extensions\desec\Support\Settings;

/**
 * Read-only and database-only on purpose: the console page is opened constantly,
 * and deSEC allows just 2000 requests per day per account.
 */
class ServerDomainsController extends Controller
{
    public function __construct(private Settings $settings, private AllocationStore $allocations)
    {
    }

    public function index(Request $request, Server $server): JsonResponse
    {
        if (!$this->canSee($request, $server)) {
            return response()->json(['domains' => []]);
        }

        return response()->json([
            'domains' => array_map(fn (array $allocation) => [
                'id' => $allocation['id'],
                'host' => $allocation['host'],
                'kind' => $allocation['kind'],
                'wildcard' => in_array('*', array_column($allocation['records'], 'subname'), true),
                'status' => $allocation['status'],
            ], $this->allocations->forServer($server->id)),
        ]);
    }

    private function canSee(Request $request, Server $server): bool
    {
        if ($this->settings->get('clientVisibility') !== 'owner') {
            return true;
        }

        return (bool) $request->user()->root_admin || (int) $server->owner_id === (int) $request->user()->id;
    }
}
