<?php

namespace bronsted;

use DI\Container;

use function DI\env;

function appService(Container $container)
{
    $env = getenv('environment');
    if (empty($env)) {
        $env = 'dev';
    }
    $filename = __DIR__ . "/${env}_config.yaml";
    $config = new AppServiceConfig($filename);
    $container->set(AppServiceConfig::class, $config);
}