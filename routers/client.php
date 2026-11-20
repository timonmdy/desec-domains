<?php

use Illuminate\Support\Facades\Route;
use Pterodactyl\BlueprintFramework\Extensions\desec\Http\Controllers as Api;
use Pterodactyl\Http\Middleware\Activity\ServerSubject;
use Pterodactyl\Http\Middleware\Api\Client\Server\AuthenticateServerAccess;
use Pterodactyl\Http\Middleware\Api\Client\Server\ResourceBelongsToServer;

Route::group([
    'prefix' => '/servers/{server}',
    'middleware' => [ServerSubject::class, AuthenticateServerAccess::class, ResourceBelongsToServer::class],
], function () {
    Route::get('/domains', [Api\ServerDomainsController::class, 'index']);
});
