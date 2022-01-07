<?php

namespace bronsted;

use DI\Container;
use PDO;

function database(Container $container)
{
    $config = $container->get(AppServiceConfig::class);
    $pdo = new PDO($config->databaseUrl);
    Db::setConnection(new DbConnection($pdo));
}
