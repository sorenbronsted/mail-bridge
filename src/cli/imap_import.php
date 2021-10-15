<?php

namespace bronsted;

$root = dirname(dirname(__DIR__));
require $root . '/vendor/autoload.php';

use Exception;

$app = bootstrap();
$client = $app->getContainer()->get(MatrixClient::class);
$imap = $app->getContainer()->get(ImapCtrl::class);

// This is for test purpose and matches current mails
$user = new User('Søren Brønsted', 'soren@bronsted.dk', 'syntest.lan', 'sb');
$user->save();
$account = new Account();
$account->user_uid = $user->uid;
$account->save();

for ($i = 1; $i <= 100; $i++) {
    echo '.';
    try {
        $filename = $i . '.mime';
        $fh = fopen($root . '/mails/' . $filename, 'r');
        $imap->import($account, $fh);
    } catch (Exception $e) {
        Log::error($e->getMessage());
        Log::error($e->getTraceAsString());
        die();
    } finally {
        fclose($fh);
    }
    exit(); // Only one
}
echo PHP_EOL;