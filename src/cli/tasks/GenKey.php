<?php

namespace bronsted;

class GenKey
{
    public function run(array $args)
    {
        echo Crypto::generateSecretKey() . PHP_EOL;
    }
}