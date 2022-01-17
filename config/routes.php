<?php

namespace bronsted;

use Slim\App;
use Slim\Routing\RouteCollectorProxy;

function routes(App $app): void
{
    // Account
    $app->get('/account/login', [AppServiceCtrl::class, 'login']);

    $app->group('/', function (RouteCollectorProxy $group) {
        $group->get('account/login/token', [AppServiceCtrl::class, 'loginToken']);
        // Old synapse api
        $group->put('transactions/{txnId}', [AppServiceCtrl::class, 'events']);
        $group->put('_matrix/app/v1/transactions/{txnId}', [AppServiceCtrl::class, 'events']);
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
