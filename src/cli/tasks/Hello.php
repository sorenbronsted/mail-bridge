<?php

namespace bronsted;

class Hello
{
    public function run(array $args)
    {
        echo 'Hello ' . $args[0] . PHP_EOL;
    }
}