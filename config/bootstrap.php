<?php

namespace bronsted;

use DI\Container;
use DI\Bridge\Slim\Bridge;
use Slim\App;
use Slim\Middleware\ContentLengthMiddleware;

function bootstrap(): App
{
    $container = new Container();
    config($container);
    logging($container);
    database($container);
    //TODO not needed anymore client($container);

    $app = Bridge::create($container);
    $app->add(new ContentLengthMiddleware());
    $app->addRoutingMiddleware();
    $app->addBodyParsingMiddleware(['application/json' => static function ($input) {
        return json_decode($input);
    }]);

    routes($app);

    return $app;
}
