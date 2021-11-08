<?php

namespace bronsted;

use DI\Container;

function appService(Container $container)
{
    //TODO P1 environment for prod, test or dev
    $file = __DIR__ . '/dev_config.yaml';
    $config = new AppServiceConfig($file);
    $container->set(AppServiceConfig::class, $config);
    $container->set(FileStore::class, new FileStore($config->fileStoreRoot));
}