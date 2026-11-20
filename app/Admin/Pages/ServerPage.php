<?php

namespace Pterodactyl\BlueprintFramework\Extensions\desec\Admin\Pages;

use Illuminate\Http\Request;
use Pterodactyl\Models\Server;
use Pterodactyl\BlueprintFramework\Extensions\desec\Dns\HostAddress;
use Pterodactyl\BlueprintFramework\Extensions\desec\Storage\AllocationStore;
use Pterodactyl\BlueprintFramework\Extensions\desec\Storage\DomainStore;
use Pterodactyl\BlueprintFramework\Extensions\desec\Support\Settings;

class ServerPage implements Page
{
    public function __construct(
        private DomainStore $domains,
        private AllocationStore $allocations,
        private Settings $settings,
    ) {
    }

    public function name(): string
    {
        return 'server';
    }

    public function data(Request $request): array
    {
        $server = Server::query()->with('allocation')->findOrFail((int) $request->query('server'));

        return [
            'server' => ['id' => $server->id, 'name' => $server->name, 'port' => $server->allocation?->port, 'publicIp' => HostAddress::publicIp($server)],
            'serverModel' => $server,
            'allocations' => $this->allocations->forServer($server->id),
            'domains' => array_map([DomainStore::class, 'view'], $this->domains->all()),
            'service' => (string) $this->settings->get('srvSubname'),
        ];
    }
}
