<?php

namespace bronsted;

use Exception;
use Psr\Log\Test\TestLogger;

class SendMailTest extends TestCase
{
    private AppServiceConfig $config;
    private FileStore $store;
    private Http $http;
    private TestLogger $logger;

    protected function setUp():void
    {
        parent::setUp();

        $this->logger = new TestLogger();
        Log::setInstance($this->logger);

        $this->config = $this->container->get(AppServiceConfig::class);
        $this->store = $this->container->get(FileStore::class);
        $this->http = $this->container->get(Http::class);
    }

    public function testRunDefault()
    {
        $user = Fixtures::user();
        $account = Fixtures::account($user);
        $account->setAccountData($this->config, Fixtures::accountData());
        $account->save();

        $smtpMock = $this->mock(Smtp::class);
        $smtpMock->expects($this->once())->method('send');

        Mail::createFromEvent($this->http, $this->store, $user, User::getAll(), 'Subject', Fixtures::event());

        $task = $this->container->get(SendMail::class);
        $task->run();
        $this->assertFalse($this->logger->hasErrorRecords());
        $this->assertEquals(0, count($this->store->getFiles()));
        $this->assertEquals(0, count(Mail::getAll()));
    }

    public function testRunNoWork()
    {
        $user = Fixtures::user();
        $account = Fixtures::account($user);
        $account->setAccountData($this->config, Fixtures::accountData());
        $account->save();

        $task = $this->container->get(SendMail::class);
        $task->run();
        $this->assertFalse($this->logger->hasErrorRecords());
        $this->assertEquals(0, count($this->store->getFiles()));
        $this->assertEquals(0, count(Mail::getAll()));
    }

    public function testFail()
    {
        $user = Fixtures::user();
        $account = Fixtures::account($user);
        $account->setAccountData($this->config, Fixtures::accountData());
        $account->save();

        $smtpMock = $this->mock(Smtp::class);
        $smtpMock->expects($this->once())->method('send')->willThrowException(new Exception('Some error', 17));

        Mail::createFromEvent($this->http, $this->store, $user, User::getAll(), 'Subject', Fixtures::event());

        $task = $this->container->get(SendMail::class);
        $task->run();
        $this->assertTrue($this->logger->hasErrorRecords());

        $mails = Mail::getAll();
        $this->assertEquals(1, count($mails));
        $this->assertNotEquals(0, $mails[0]->fail_code);
    }
}