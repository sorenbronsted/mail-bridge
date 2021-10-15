<?php

namespace bronsted;

use Exception;
use Throwable;

require dirname(dirname(__DIR__)) . '/vendor/autoload.php';

function send()
{
    // This happens on the fly
}

function fetch()
{

}

function import()
{

}

function run()
{
    $lock = '/var/run/mail_run.pid';

    $app = bootstrap();

    if (file_exists($lock)) {
        Log::info('Allready running, bye');
        return;
    }
    try {
        $ok = @file_put_contents($lock, getmypid());
        if (!$ok) {
            throw new Exception('Writing lock file failed');
        }
        send();
        fetch();
        import();
    } catch (Throwable $t) {
        Log::error($t->getMessage());
    } finally {
        if (file_exists($lock)) {
            unlink($lock);
        }
    }
}

run();
