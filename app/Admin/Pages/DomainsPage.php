<?php

namespace Pterodactyl\BlueprintFramework\Extensions\desec\Admin\Pages;

use Illuminate\Http\Request;
use Pterodactyl\BlueprintFramework\Extensions\desec\Services\DomainService;
use Pterodactyl\BlueprintFramework\Extensions\desec\Storage\AllocationStore;
use Pterodactyl\BlueprintFramework\Extensions\desec\Storage\DomainStore;
use Pterodactyl\BlueprintFramework\Extensions\desec\Support\Settings;

class DomainsPage implements Page
{
    public function __construct(
        private DomainStore $domains,
        private AllocationStore $allocations,
        private DomainService $service,
        private Settings $settings,
    ) {
    }

    public function name(): string
    {
        return 'domains';
    }

    public function data(Request $request): array
    {
        return [
            'domains' => array_map(fn (array $domain) => DomainStore::view($domain) + [
                'status' => $this->service->status($domain),
                'allocationCount' => $this->allocations->countForDomain($domain['id']),
            ], $this->domains->all()),
            'discoverable' => $this->settings->apiToken() !== '' ? $this->service->discoverable() : [],
            'hasDefaultToken' => $this->settings->apiToken() !== '',
        ];
    }
}
