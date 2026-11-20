<?php

namespace Pterodactyl\BlueprintFramework\Extensions\desec\Admin;

use Pterodactyl\BlueprintFramework\Extensions\desec\Admin\Pages\DomainPage;
use Pterodactyl\BlueprintFramework\Extensions\desec\Admin\Pages\DomainsPage;
use Pterodactyl\BlueprintFramework\Extensions\desec\Admin\Pages\Page;
use Pterodactyl\BlueprintFramework\Extensions\desec\Admin\Pages\ServerPage;
use Pterodactyl\BlueprintFramework\Extensions\desec\Admin\Pages\SettingsPage;

class PageRegistry
{
    private const PAGES = [
        'settings' => SettingsPage::class,
        'domains' => DomainsPage::class,
        'domain' => DomainPage::class,
        'server' => ServerPage::class,
    ];

    public function resolve(string $view): Page
    {
        return app(self::PAGES[$view] ?? self::PAGES['settings']);
    }
}
