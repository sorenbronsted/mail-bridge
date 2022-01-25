<?php

namespace bronsted;

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

function routes(App $app): void
{
    $app->get('/health', function (Request $request, Response $response) {
        $response->getBody()->write("I'm alive");
        return $response;
    });

    $app->group('/', function (RouteCollectorProxy $group) {
        $group->get('account/login', [AppServiceCtrl::class, 'login']);
        $group->post('upload/{user_id}', [AppServiceCtrl::class, 'upload']);
        // Old synapse api
        $group->put('transactions/{txnId}', [AppServiceCtrl::class, 'events']);
        $group->put('_matrix/app/v3/transactions/{txnId}', [AppServiceCtrl::class, 'events']);
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
