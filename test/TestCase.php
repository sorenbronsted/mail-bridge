<?php

namespace bronsted;

use PHPUnit\Framework\TestCase as FrameworkTestCase;

class TestCase extends FrameworkTestCase
{
    // https://odan.github.io/2020/06/09/slim4-testing.html
    use AppTestTrait;

    protected function setUp(): void
    {
        global $app;
        $this->boot();
        $app = $this->app;
        Fixtures::clean();
    }
}
