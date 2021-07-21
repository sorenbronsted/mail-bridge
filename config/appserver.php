<?php

use bronsted\AppServerConfig;
use DI\Container;

function appServer(Container $container)
{
    $synapse = getenv('SYNAPSE') ? getenv('SYNAPSE') : 'localhost';

    $config = new AppServerConfig();
    $config->baseUrl = "http://$synapse:8008";
    $config->tokenAppServer = 'wfghWEGh3wgWHEf3478sHFWF';
    $config->tokenHomeServer = 'ugw8243igya57aaABGFfgeyu';
    $config->domain = 'syntest.lan';
    $config->key = 'n4Kbu29Ycc8EG2d23myHBQMUcEHeUDIwKB9OiPbT0kc=';
    $config->storeInbox = '../mails/inbox';
    $config->storeFailed = '../mails/failed';

    $container->set(AppServerConfig::class, $config);
}