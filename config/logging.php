<?php

namespace bronsted;

use DI\Container;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

function logging(Container $container)
{
    $log = new Logger('logger', []);
    $log->pushHandler(new StreamHandler(STDERR, Logger::DEBUG));
    Log::setInstance($log);

    $container->set(Logger::class, $log);
    $container->set(LoggerInterface::class, $log);
}