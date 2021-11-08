<?php

namespace bronsted;

use DI\Container;

use function DI\env;

function appService(Container $container)
{
    $filename = dirname(__DIR__) . "/mail-bridge.yaml";
    $config = new AppServiceConfig($filename);
    $container->set(AppServiceConfig::class, $config);
}