<?php

namespace bronsted;

require dirname(__DIR__) . '/vendor/autoload.php';

use Exception;

$app = bootstrap();
$http = $app->getContainer()->get(Http::class);
$user = $app->getContainer()->get(User::class);

$imap = new Imap($http, $user);

$failed = [];

for ($i = 1; $i <= 100; $i++) {
    echo ".";
    try {
        $filename = $i . '.mime';
        $fh = fopen(dirname(__DIR__) . '/mails/' . $filename, 'r');
        $imap->import($fh);
    } catch (Exception $e) {
        echo PHP_EOL . $i . ' ' . $e->getMessage() . PHP_EOL;
        $failed[] = $i;
        die($e);
    } finally {
        fclose($fh);
    }
}

if (!empty($failed)) {
    echo "Doing failed at a slower rate" . PHP_EOL;
    foreach ($failed as $i) {
        echo ".";
        try {
            $filename = $i . '.mime';
            $fh = fopen(dirname(__DIR__) . '/mails/' . $filename, 'r');
            $imap->import($fh);
            sleep(1);
        } catch (Exception $e) {
            echo PHP_EOL . $i . ' ' . $e->getMessage() . ' again ' . PHP_EOL;
            //die($e);
        } finally {
            fclose($fh);
        }
    }
}
echo PHP_EOL;
