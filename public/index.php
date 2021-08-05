<?php

namespace bronsted;

use Exception;
use Psr\Log\LoggerInterface;
use Slim\ResponseEmitter;

require '../vendor/autoload.php';

try {
    $app = bootstrap();
    $app->run();
} catch (Exception $e) {
    $log = $app->getContainer()->get(LoggerInterface::class);
    $log->error($e->getMessage());
    $log->error($e->getTraceAsString());

    $response = $app->getResponseFactory()->createResponse(500);
    $responseEmitter = new ResponseEmitter();
    $responseEmitter->emit($response);
}
