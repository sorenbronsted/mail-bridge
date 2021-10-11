<?php
date_default_timezone_set('UTC');

//----------------------------
// DATABASE CONFIGURATION
//----------------------------

/*

Valid types (adapters) are Postgres & MySQL:

'type' must be one of: 'pgsql' or 'mysql' or 'sqlite'

*/
return array(
    'db' => array(
        'development' => array(
            'type' => 'sqlite',
            'database' => 'db/db.sqlite',
            'host' => 'localhost',
            'port' => '',
            'user' => '',
            'password' => ''
        )

    ),
    'migrations_dir' => array('default' => RUCKUSING_WORKING_BASE . '/db/migrations'),
    'db_dir' => RUCKUSING_WORKING_BASE . '/db',
    'log_dir' => '/tmp/db_migrations/logs',
    'ruckusing_base' => RUCKUSING_WORKING_BASE . '/vendor/ruckusing/ruckusing-migrations'
);
