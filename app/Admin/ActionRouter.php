<?php

namespace Pterodactyl\BlueprintFramework\Extensions\desec\Admin;

use Illuminate\Http\Request;
use Pterodactyl\BlueprintFramework\Extensions\desec\Admin\Actions\AllocationActions;
use Pterodactyl\BlueprintFramework\Extensions\desec\Admin\Actions\DomainActions;
use Pterodactyl\BlueprintFramework\Extensions\desec\Admin\Actions\RecordActions;
use Pterodactyl\BlueprintFramework\Extensions\desec\Admin\Actions\SettingsActions;

class ActionRouter
{
    private const ACTIONS = [
        'settings.save' => [SettingsActions::class, 'save'],
        'settings.test' => [SettingsActions::class, 'test'],
        'domain.connect' => [DomainActions::class, 'connect'],
        'domain.token' => [DomainActions::class, 'token'],
        'domain.disconnect' => [DomainActions::class, 'disconnect'],
        'domain.refresh' => [DomainActions::class, 'refresh'],
        'record.save' => [RecordActions::class, 'save'],
        'record.delete' => [RecordActions::class, 'delete'],
        'allocation.create' => [AllocationActions::class, 'create'],
        'allocation.release' => [AllocationActions::class, 'release'],
    ];

    /** @return string the success message to show the administrator */
    public function run(Request $request): string
    {
        $action = (string) $request->input('action');
        abort_unless(isset(self::ACTIONS[$action]), 422, 'Unknown action.');

        [$class, $method] = self::ACTIONS[$action];

        return app($class)->{$method}($request, (int) $request->user()->id);
    }
}
