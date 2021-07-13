<?php

namespace bronsted;

use DI\Container;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Middleware\ContentLengthMiddleware;

function bootstrap(): App
{
    $container = new Container();
    logging($container);
    database();
    user($container);
    client($container);

    appServer($container);

    AppFactory::setContainer($container);
    $app = AppFactory::create();

    $app->add(new ContentLengthMiddleware());
    $app->addRoutingMiddleware();
    $app->addBodyParsingMiddleware(['application/json' => static function ($input) {
        return json_decode($input);
    }]);

    routes($app);

    return $app;
}
