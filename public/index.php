<?php

namespace bronsted;

use Psr\Http\Message\ServerRequestInterface as Request;
use React\EventLoop\Loop;
use React\Http\HttpServer;
use React\Socket\SocketServer;
use Slim\App;
use Slim\Psr7\Factory\StreamFactory;
use SplFileInfo;
use Symfony\Component\Mime\MimeTypes;
use Throwable;

require dirname(__DIR__) . '/vendor/autoload.php';

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

    runTasks($app);
    $http = new HttpServer(
        function (Request $request) use ($app) {
            $response = null;
            try {
                // Static file content?
                $fileInfo = new SplFileInfo( __DIR__ . '/' . $request->getUri()->getPath());
                if ($fileInfo->isFile()) {
                    $fileType = MimeTypes::getDefault()->getMimeTypes($fileInfo->getExtension())[0];
                    $body = (new StreamFactory())->createStreamFromFile($fileInfo->getPathname());
                    $response = $app->getResponseFactory()->createResponse(200)
                        ->withHeader('Content-Type', $fileType)
                        ->withBody($body);
                }
                else {
                    $response = $app->handle($request);
                }
            } catch (Throwable $e) {
                $code = $e->getCode() >= 100 && $e->getCode() <= 599 ? $e->getCode() : 500;
                if ($code >= 500) {
                    Log::error($e);
                }
                $response = $app->getResponseFactory()->createResponse($code);
            }
            Log::info('{method} {uri} {code}', [
                'method' => $request->getMethod(),
                'uri' => $request->getUri(),
                'code' => $response->getStatusCode()
            ]);
            return $response;
        }
    );
    $interface = isset($argv['interface']) ? isset($argv['interface']) : '0.0.0.0';
    $port = isset($argv['port']) ? isset($argv['port']) : '8000';
    $socket = new SocketServer($interface . ':' . $port);
    $http->listen($socket);
}

run($argv);
