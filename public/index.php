<?php

namespace bronsted;

use Amp\Http\Server\HttpServer;
use Amp\Http\Server\RequestHandler\CallableRequestHandler;
use Amp\Loop;
use Amp\Socket\Server;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;
use Symfony\Component\Mime\MimeTypes;
use Throwable;
use SplFileInfo;

require dirname(__DIR__) . '/vendor/autoload.php';

function runTasks(App $app): array
{
    $ids = [];

    //$tasks = [FetchMail::class, ImportMail::class, SendMail::class];
    $tasks = [ImportMail::class];
    foreach($tasks as $taskClass) {
        $ids[] = Loop::repeat(2000, function() use($app, $taskClass) {
            $task = $app->getContainer()->get($taskClass);
            $task->run();
        });
    }
    return $ids;
}

function toPsrServerRequest(App $app, Request $request): ServerRequestInterface
{
    $factory = $app->getContainer()->get(ServerRequestFactory::class);
    $psrRequest = $factory->createServerRequest($request->getMethod(), $request->getUri())
        ->withCookieParams($request->getCookies());
    //$psrRequest->withBody($request->getBody());
    return $psrRequest;

    $psrRequest = new \Slim\Psr7\Request(
        $request->getMethod(),
        (new UriFactory())->createUri($request->getUri()),
        new \Slim\Psr7\Headers($request->getHeaders()),
        $request->getCookies(),
        [],
        yield $request->getBody()->buffer(),
        [] // UploadedFiles
    );
}

function toAmServerResponse(ResponseInterface $request): Response
{
    return new Response($request->getStatusCode(), $request->getHeaders(), $request->getBody());
}

function runServer(App $app, array $argv): HttpServer
{
    $interface = isset($argv['interface']) ? isset($argv['interface']) : '0.0.0.0';
    $port = isset($argv['port']) ? isset($argv['port']) : '8000';

    $servers = [
        Server::listen($interface . ':' . $port),
    ];

    return new HttpServer($servers, new CallableRequestHandler(
        function (Request $request) use ($app) {
            $response = null;
            try {
                // Static file content?
                $fileInfo = new SplFileInfo(__DIR__ . '/' . $request->getUri()->getPath());
                if ($fileInfo->isFile()) {
                    $fileType = MimeTypes::getDefault()->getMimeTypes($fileInfo->getExtension())[0];
                    $body = (new StreamFactory())->createStreamFromFile($fileInfo->getPathname());
                    $response = $app->getResponseFactory()->createResponse(200)
                        ->withHeader('Content-Type', $fileType)
                        ->withBody($body);
                } else {
                    $response = $app->handle(toPsrServerRequest($app, $request));
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
            return toAmServerResponse($response);
        }
    ), $app->getContainer()->get(LoggerInterface::class));
}

function run(array $argv)
{
    $app = bootstrap();

    Loop::run(function () use($app, $argv) {
        $taskIds = []; //runTasks($app);
        $server = runServer($app, $argv);
        yield $server->start();

        // Stop the server when SIGINT is received (this is technically optional, but it is best to call Server::stop()).
        Loop::onSignal(SIGINT, function (string $watcherId) use ($server, $taskIds) {
            foreach($taskIds as $taskId) {
                Loop::cancel($taskId);
            }
            Loop::cancel($watcherId);
            yield $server->stop();
        });
    });
}

run($argv);
