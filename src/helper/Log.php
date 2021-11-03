<?php

namespace bronsted;

use Psr\Log\LoggerInterface;
use Throwable;

class Log
{
    private static LoggerInterface $instance;

    public static function setInstance(LoggerInterface $instance)
    {
        self::$instance = $instance;
    }

    public static function __callStatic($name, $arguments)
    {
        if ($arguments[0] instanceof Throwable) {
            $th = $arguments[0];
            self::$instance->$name($th->getMessage() . ' code: ' . $th->getCode() .  ' ' . $th->getFile() . ':' . $th->getLine());
            foreach($th->getTrace() as $trace) {
                $trace = (object)$trace;
                self::$instance->$name($trace->function . ' ' . ($trace->file ?? '') . ':' . ($trace->line ?? ''));
            }
            }
        else {
            self::$instance->$name($arguments[0], $arguments[1] ?? []);
        }
    }
}
