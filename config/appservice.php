<?php

namespace bronsted;

use DI\Container;

function appService(Container $container)
{
    //TODO P1 How is config is loaded?
    $synapse = getenv('SYNAPSE') ? getenv('SYNAPSE') : 'localhost';

    $config = new AppServiceConfig();
    $config->baseUrl = "http://$synapse:8008";
    $config->tokenMine = 'wfghWEGh3wgWHEf3478sHFWF';
    $config->tokenGuest = ['ugw8243igya57aaABGFfgeyu'];
    $config->domain = 'syntest.lan';
    $config->key = 'n4Kbu29Ycc8EG2d23myHBQMUcEHeUDIwKB9OiPbT0kc=';
    $config->cookieName = 'mailBridge';

    $container->set(AppServiceConfig::class, $config);
    $container->set(FileStore::class, new FileStore('/var/tmp'));
}