<?php

namespace bronsted;

use Psr\Log\LoggerInterface;

class LogTest extends TestCase
{
    public function testLog()
    {
        Log::setInstance($this->container->get(LoggerInterface::class));
        //Log::info('test');
        $this->assertTrue(true);
    }
}