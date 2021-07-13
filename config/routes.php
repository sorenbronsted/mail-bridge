<?php

namespace bronsted;

use Slim\App;

function routes(App $app): void
{
    $app->put('/_matrix/app/v1/transactions/{txnId}', [AppService::class, 'events']);
    $app->put('/transactions/{txnId}', [AppService::class, 'events']);

    $app->get('/users/{userId}', [AppService::class, 'hasUser']);
    $app->get('/_matrix/app/v1/users/{userId}', [AppService::class, 'hasUser']);

    $app->get('/rooms/{roomAlias}', [AppService::class, 'hasRoom']);
    $app->get('/_matrix/app/v1/rooms/{roomAlias}', [AppService::class, 'hasRoom']);
}
