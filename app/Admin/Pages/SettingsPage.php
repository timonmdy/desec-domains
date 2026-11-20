<?php

namespace Pterodactyl\BlueprintFramework\Extensions\desec\Admin\Pages;

use Illuminate\Http\Request;
use Pterodactyl\BlueprintFramework\Extensions\desec\Support\Settings;
use Pterodactyl\BlueprintFramework\Extensions\desec\Support\SettingsSchema;

class SettingsPage implements Page
{
    public function __construct(private Settings $settings)
    {
    }

    public function name(): string
    {
        return 'settings';
    }

    public function data(Request $request): array
    {
        return [
            'sections' => SettingsSchema::sections(),
            'values' => $this->settings->formValues(),
            'secretSet' => ['apiToken' => $this->settings->secretIsSet('apiToken')],
        ];
    }
}
