<?php

namespace Pterodactyl\BlueprintFramework\Extensions\desec\Admin\Actions;

use Illuminate\Http\Request;
use Pterodactyl\BlueprintFramework\Extensions\desec\Services\DomainService;
use Pterodactyl\BlueprintFramework\Extensions\desec\Storage\AllocationStore;
use Pterodactyl\BlueprintFramework\Extensions\desec\Storage\DomainStore;

class DomainActions extends Action
{
    public function __construct(DomainStore $domains, AllocationStore $allocations, private DomainService $service)
    {
        parent::__construct($domains, $allocations);
    }

    public function connect(Request $request, int $userId): string
    {
        $request->validate(['name' => ['required', 'string', 'max:253'], 'token' => ['nullable', 'string', 'max:200']]);
        $this->service->connect((string) $request->input('name'), (string) $request->input('token'), $userId);

        return 'Domain connected.';
    }

    public function token(Request $request, int $userId): string
    {
        $request->validate(['token' => ['nullable', 'string', 'max:200']]);
        $token = (string) $request->input('token');
        $this->service->setToken($this->domain($request), $token, $userId);

        return trim($token) === '' ? 'Domain token removed. The default token applies again.' : 'Domain token saved.';
    }

    public function disconnect(Request $request, int $userId): string
    {
        $this->service->disconnect($this->domain($request), $userId);

        return 'Domain disconnected. Nothing was deleted at deSEC.';
    }

    public function refresh(Request $request): string
    {
        $this->service->forget($this->domain($request));

        return 'Refreshed from deSEC.';
    }
}
