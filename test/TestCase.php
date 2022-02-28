<?php

namespace bronsted;

use PHPUnit\Framework\TestCase as FrameworkTestCase;
use Psr\Log\Test\TestLogger;
use React\EventLoop\Loop;
use React\Promise\Deferred;

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

    protected function createPromiseResolved($value = null, $delay = 0.01)
    {
        $deferred = new Deferred();

        Loop::get()->addTimer($delay, function () use ($deferred, $value) {
            $deferred->resolve($value);
        });

        return $deferred->promise();
    }

    protected function createPromiseRejected($value = null, $delay = 0.01)
    {
        $deferred = new Deferred();

        Loop::get()->addTimer($delay, function () use ($deferred, $value) {
            $deferred->reject($value);
        });

        return $deferred->promise();
    }

}
