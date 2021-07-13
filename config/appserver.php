<?php

use bronsted\AppServerConfig;
use DI\Container;

function appServer(Container $container)
{
    $config = new AppServerConfig(
        'http://localhost:8008',
        'wfghWEGh3wgWHEf3478sHFWF',
        'ugw8243igya57aaABGFfgeyu',
        'syntest.lan'
    );
    $container->set(AppServerConfig::class, $config);
}