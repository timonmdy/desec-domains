<?php

namespace Pterodactyl\BlueprintFramework\Extensions\desec\Admin\Actions;

use Illuminate\Http\Request;
use Pterodactyl\BlueprintFramework\Extensions\desec\Storage\AllocationStore;
use Pterodactyl\BlueprintFramework\Extensions\desec\Storage\DomainStore;

abstract class Action
{
    public function __construct(protected DomainStore $domains, protected AllocationStore $allocations)
    {
    }

    protected function domain(Request $request, string $key = 'id'): array
    {
        return $this->domains->find((int) $request->input($key)) ?? abort(404, 'Unknown domain.');
    }
}
