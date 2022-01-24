<?php

namespace bronsted;

use DI\Container;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use Psr\Log\LoggerInterface;

function logging(Container $container)
{
    $processor = new PsrLogMessageProcessor(null, true);
    $log = new Logger('logger', [], [$processor]);
    $log->pushHandler(new StreamHandler('php://stderr', Logger::DEBUG));
    Log::setInstance($log);

    $container->set(Logger::class, $log);
    $container->set(LoggerInterface::class, $log);
}