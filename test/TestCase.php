<?php

namespace bronsted;

use PHPUnit\Framework\TestCase as FrameworkTestCase;
use Psr\Log\Test\TestLogger;

class TestCase extends FrameworkTestCase
{
    // https://odan.github.io/2020/06/09/slim4-testing.html
    use AppTestTrait;

    protected TestLogger $logger;

    protected function setUp(): void
    {
        $this->boot();
        $this->container->get(FileStore::class)->cleanAll();
        Fixtures::clean();
        $this->logger = new TestLogger();
        Log::setInstance($this->logger);
    }
}
