<?php

namespace bronsted;

use Slim\App;
use Slim\Routing\RouteCollectorProxy;

function routes(App $app): void
{
    // Account
    $app->get('/account/login', [AccountCtrl::class, 'login']);

    $app->group('/', function (RouteCollectorProxy $group) {
        $group->get('account/login/token', [AccountCtrl::class, 'loginToken']);
        // Old synapse api
        $group->put('transactions/{txnId}', [AppServiceCtrl::class, 'events']);
        $group->get('users/{userId:@.+}', [AppServiceCtrl::class, 'hasUser']);
        $group->get('rooms/{roomAlias}', [AppServiceCtrl::class, 'hasRoom']);
    })->add(ServiceAuthenticateCtrl::class);

    $app->group('/_matrix/app/v1', function (RouteCollectorProxy $group) {
        $group->put('/transactions/{txnId}', [AppServiceCtrl::class, 'events']);
        $group->get('/users/{userId}', [AppServiceCtrl::class, 'hasUser']);
        $group->get('/rooms/{roomAlias}', [AppServiceCtrl::class, 'hasRoom']);
    })->add(ServiceAuthenticateCtrl::class);

    $app->group('/account', function (RouteCollectorProxy $group) {
        $group->get('', [AccountCtrl::class, 'index']);
        $group->get('/user', [AccountCtrl::class, 'getByUser']);
        $group->get('/create', [AccountCtrl::class, 'create']);
        $group->get('/{uid}/show', [AccountCtrl::class, 'show']);
        $group->get('/{uid}/edit', [AccountCtrl::class, 'edit']);
        $group->post('/save', [AccountCtrl::class, 'save']);
        $group->get('/{uid}/delete', [AccountCtrl::class, 'delete']);
        $group->get('/{uid}/verify', [AccountCtrl::class, 'verify']);
    })->add(UserAuthenticateCtrl::class);
}
