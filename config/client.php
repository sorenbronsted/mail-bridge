<?php

namespace bronsted;

use DI\Container;
use JustSteveKing\HttpSlim\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;

function client(Container $container)
{
    $client = HttpClient::build(
        new Psr18Client(), // our client
        new Psr18Client(), // our request factory
        new Psr18Client() // our stream factory
    );
    $container->set(HttpClient::class, $client);
}