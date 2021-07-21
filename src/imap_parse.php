<?php

namespace bronsted;

require dirname(__DIR__) . '/vendor/autoload.php';

use Exception;

$app = bootstrap();
$client = $app->getContainer()->get(MatrixClient::class);
$user = $app->getContainer()->get(User::class);

$imap = new Imap($client, $user);

for ($i = 1; $i <= 100; $i++) {
    try {
        $filename = $i . '.mime';
        $fh = fopen(dirname(__DIR__) . '/mails/' . $filename, 'r');
        $imap->import($fh);
    } catch (Exception $e) {
        Log::error($e->getMessage(), $e->getTraceAsString());
        die();
    } finally {
        fclose($fh);
    }
}
