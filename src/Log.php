<?php

namespace bronsted;

use Psr\Log\LoggerInterface;

class Log
{
    private static LoggerInterface $instance;

    public static function setInstance(LoggerInterface $instance)
    {
        self::$instance = $instance;
    }

    public static function __callStatic($name, $arguments)
    {
        self::$instance->$name($arguments[0], $arguments[1] ?? []);
    }
}
