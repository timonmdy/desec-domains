<?php

namespace Pterodactyl\BlueprintFramework\Extensions\desec\Admin\Pages;

use Illuminate\Http\Request;

interface Page
{
    public function name(): string;

    public function data(Request $request): array;
}
