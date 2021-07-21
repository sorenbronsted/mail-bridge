<?php

namespace bronsted;

require dirname(__DIR__) . '/vendor/autoload.php';

echo Crypto::generateSecretKey() . PHP_EOL;
