<?php

namespace bronsted;

use stdClass;

function database()
{
    $sqlite = new stdClass();
    $sqlite->driver = 'sqlite';
    $sqlite->name = dirname(__DIR__) . '/db/db.sqlite';
    $sqlite->user = '';
    $sqlite->password = '';

    $config = new stdClass();
    $config->default = $sqlite;
    DbConnection::setConfig($config);
}
