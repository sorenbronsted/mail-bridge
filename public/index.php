<?php

namespace bronsted;

use Exception;
use Slim\ResponseEmitter;

require '../vendor/autoload.php';

try {
    $app = bootstrap();
    $app->run();
} catch (Exception $e) {
    Log::error($e);
    $code = $e->getCode() >= 100 && $e->getCode() <= 599 ? $e->getCode() : 500;
    $response = $app->getResponseFactory()->createResponse($code);
    $responseEmitter = new ResponseEmitter();
    $responseEmitter->emit($response);
}
