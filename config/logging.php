<?php

namespace bronsted;

use DI\Container;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

function logging(Container $container)
{
    $path = dirname(__DIR__) . '/logs';
    if (!file_exists($path)) {
        mkdir($path, 0777, true);
    }

    $container->set(Logger::class, new Logger('logger'));
    $container->set(LoggerInterface::class, function(ContainerInterface $container) use($path) {
        $log = $container->get(Logger::class);
        $log->pushHandler(new StreamHandler('php://stderr', Logger::DEBUG));
        return $log;
    });
}