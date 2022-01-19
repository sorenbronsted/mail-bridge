<?php

namespace bronsted;

use Psr\Http\Message\ServerRequestInterface as Request;
use React\EventLoop\Loop;
use React\Http\HttpServer;
use React\Socket\SocketServer;
use Slim\App;
use Throwable;

use function React\Promise\Timer\sleep;

require 'vendor/autoload.php';

function runTasks(App $app)
{
    $loop = Loop::get();
    // fetch mail
    $fetch = $app->getContainer()->get(FetchMail::class);
    $loop->addPeriodicTimer(1, function($timer) use($fetch) {
        //Log::info("fectching mail");
        $fetch->run();
    });

    // import mail
    $import = $app->getContainer()->get(ImportMail::class);
    $loop->addPeriodicTimer(1, function($timer) use($import) {
        //Log::info("importing mail");
        $import->run();
    });

    // send mail
    $send = $app->getContainer()->get(SendMail::class);
    $loop->addPeriodicTimer(1, function($timer) use($send) {
        //Log::info("sending mail");
        $send->run();
    });
}

function run(array $argv)
{
    $app = bootstrap();
    //The stand way: $app->run();

    runTasks($app);
    $http = new HttpServer(
        function (Request $request) use ($app) {
            $response = null;
            try {
                $response = $app->handle($request);
            } catch (Throwable $e) {
                Log::error($e);
                $code = $e->getCode() >= 100 && $e->getCode() <= 599 ? $e->getCode() : 500;
                $response = $app->getResponseFactory()->createResponse($code);
            }
            return $response;
        }
    );
    $socket = new SocketServer('127.0.0.1:8000');
    $http->listen($socket);
}

run($argv);
