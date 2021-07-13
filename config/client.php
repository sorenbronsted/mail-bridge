<?php

namespace bronsted;

use DI\Container;
use GuzzleHttp\Client;

function client(Container $container)
{
    $container->set(Client::class, new Client());
}