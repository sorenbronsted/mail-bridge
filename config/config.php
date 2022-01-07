<?php

namespace bronsted;

use DI\Container;

function config(Container $container)
{
    $filename = dirname(__DIR__) . "/mail-bridge.yaml";
    $config = new AppServiceConfig($filename);
    $container->set(AppServiceConfig::class, $config);
}