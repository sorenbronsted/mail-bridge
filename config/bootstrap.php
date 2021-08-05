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
    client($container);

    appService($container);

    AppFactory::setContainer($container);
    $app = AppFactory::create();

    $app->add(new ContentLengthMiddleware());
    $app->addRoutingMiddleware();
    $app->addBodyParsingMiddleware(['application/json' => static function ($input) {
        return json_decode($input);
    }]);

    mail($container);
    file($container);
    routes($app);

    return $app;
}
