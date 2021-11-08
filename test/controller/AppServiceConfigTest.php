<?php

namespace bronsted;

use PHPUnit\Framework\TestCase;

class AppServiceConfigTest extends TestCase
{
    public function testEmptyFile()
    {
        $file = '/tmp/' . uniqid() . '.yaml';
        touch($file);
        $this->expectExceptionMessageMatches('/^Invalid content/');
        new AppServiceConfig($file);
    }

    public function testMissingProperty()
    {
        $file = '/tmp/' . uniqid() . '.yaml';
        file_put_contents($file, "x: y");
        $this->expectExceptionMessageMatches('/^Missing config/');
        new AppServiceConfig($file);
    }
}