<?php

namespace bronsted;

use Exception;
use Slim\ResponseEmitter;

require '../vendor/autoload.php';

try {
    $app = bootstrap();
    $app->run();
} catch (Exception $e) {
    Log::error($e->getMessage() . ' code: ' . $e->getCode() .  ' ' . $e->getFile() . ':' . $e->getLine());
    foreach($e->getTrace() as $trace) {
        $trace = (object)$trace;
        Log::error($trace->function . ' ' . $trace->file . ':' . $trace->line);
    }
    $response = $app->getResponseFactory()->createResponse(500);
    $responseEmitter = new ResponseEmitter();
    $responseEmitter->emit($response);
}
