<?php

namespace bronsted;

use Exception;
use Psr\Log\Test\TestLogger;

class SendMailTest extends TestCase
{
    private AppServiceConfig $config;
    private FileStore $store;

    protected function setUp():void
    {
        parent::setUp();
        $this->config = $this->container->get(AppServiceConfig::class);
        $this->store = $this->container->get(FileStore::class);
    }

    public function testRunDefault()
    {
        $user = Fixtures::puppet($this->config);
        $account = Fixtures::account($user);
        $account->setAccountData($this->config, Fixtures::accountData());
        $account->save();
        $mail = Fixtures::mailFromFile($account, $this->store, 'direct.mime');
        $mail->action = Mail::ActionSend;
        $mail->save();

        $this->mock(Smtp::class)->expects($this->once())->method('send');

        $task = $this->container->get(SendMail::class);
        $task->run();
        $this->assertFalse($this->logger->hasErrorRecords());
        $this->assertEquals(0, count($this->store->getFiles()));
        $this->assertEquals(0, count(Mail::getAll()));
    }

    public function testRunNoWork()
    {
        $user = Fixtures::puppet($this->config);
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
        $user = Fixtures::puppet($this->config);
        $account = Fixtures::account($user);
        $account->setAccountData($this->config, Fixtures::accountData());
        $account->save();
        $mail = Fixtures::mailFromFile($account, $this->store, 'direct.mime');
        $mail->action = Mail::ActionSend;
        $mail->save();

        $this->mock(Smtp::class)
            ->expects($this->once())
            ->method('send')
            ->willThrowException(new Exception('Some error', 17));

        $task = $this->container->get(SendMail::class);
        $task->run();
        $this->assertTrue($this->logger->hasErrorRecords());

        $mails = Mail::getAll();
        $this->assertEquals(1, count($mails));
        $this->assertNotEquals(0, $mails[0]->fail_code);
    }
}