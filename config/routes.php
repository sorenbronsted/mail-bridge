<?php

namespace bronsted;

use Slim\App;

function routes(App $app): void
{
    $app->put('/_matrix/app/v1/transactions/{txnId}', [AppServiceCtrl::class, 'events']);
    $app->put('/transactions/{txnId}', [AppServiceCtrl::class, 'events']);

    $app->get('/users/{userId:@.+}', [AppServiceCtrl::class, 'hasUser']);
    $app->get('/_matrix/app/v1/users/{userId}', [AppServiceCtrl::class, 'hasUser']);

    $app->get('/rooms/{roomAlias}', [AppServiceCtrl::class, 'hasRoom']);
    $app->get('/_matrix/app/v1/rooms/{roomAlias}', [AppServiceCtrl::class, 'hasRoom']);

    $app->put('/account/register/{userId:@.+}', [AppServiceCtrl::class, 'addAccount']);
    $app->get('/account/{userId:@.+}', [AppServiceCtrl::class, 'hasAccount']);
}
