<?php

namespace bronsted;

require dirname(__DIR__) . '/vendor/autoload.php';

use Exception;

$app = bootstrap();
$client = $app->getContainer()->get(MatrixClient::class);
$imap = $app->getContainer()->get(Imap::class);

for ($i = 1; $i <= 100; $i++) {
    echo '.';
    try {
        $filename = $i . '.mime';
        $fh = fopen(dirname(__DIR__) . '/mails/' . $filename, 'r');
        $imap->import($fh);
    } catch (Exception $e) {
        Log::error($e->getMessage(), $e->getTrace());
        die();
    } finally {
        fclose($fh);
    }
}
echo PHP_EOL;