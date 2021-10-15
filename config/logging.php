<?php

namespace bronsted;

use DI\Container;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

function logging(Container $container)
{
    $path = dirname(__DIR__) . '/logs';
    if (!file_exists($path)) {
        mkdir($path, 0777, true);
    }
    $path = 'php://stderr';

    $log = new Logger('logger', []);
    $log->pushHandler(new StreamHandler($path, Logger::DEBUG));
    Log::setInstance($log);
    $container->set(Logger::class, $log);
    $container->set(LoggerInterface::class, $log);
}