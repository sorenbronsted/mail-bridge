<?php

use bronsted\AppServerConfig;
use DI\Container;

function appServer(Container $container)
{
    $synapse = getenv('SYNAPSE') ? getenv('SYNAPSE') : 'localhost';
    $config = new AppServerConfig(
        "http://$synapse:8008",
        'wfghWEGh3wgWHEf3478sHFWF',
        'ugw8243igya57aaABGFfgeyu',
        'syntest.lan'
    );
    $container->set(AppServerConfig::class, $config);
}