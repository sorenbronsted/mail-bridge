<?php

namespace bronsted;

use DateTime;
use Exception;
use Psr\Log\Test\TestLogger;

class FetchMailTest extends TestCase
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
        $user = Fixtures::puppet($this->config->domain);
        $account = Fixtures::account($user);
        $account->setAccountData($this->config, Fixtures::accountData());
        $account->updated = new DateTime('-10 min');
        $account->save();

        $imapMock = $this->mock(Imap::class);
        $imapMock->expects($this->once())->method('open')->withAnyParameters();
        $imapMock->expects($this->once())->method('sort')->withAnyParameters();
        $imapMock->expects($this->once())->method('count')->willReturn(1);
        $imapMock->expects($this->once())->method('header')->willReturn((object)['udate' => (new DateTime())->format('U')]);
        $imapMock->expects($this->once())->method('message')->willReturn(file_get_contents(dirname(__DIR__) . '/data/multi_recipients.mime'));

        $task = $this->container->get(FetchMail::class);
        $task->run();
        $this->assertFalse($this->logger->hasErrorRecords());
        $this->assertEquals(1, count($this->store->getFiles()));
        $this->assertNotEquals($account->updated, Account::getByUid($account->uid)->updated);
    }

    public function testRunNoWork()
    {
        $user = Fixtures::puppet($this->config->domain);
        $account = Fixtures::account($user);
        $account->setAccountData($this->config, Fixtures::accountData());
        $account->save();

        $task = $this->container->get(FetchMail::class);
        $task->run();
        $this->assertFalse($this->logger->hasErrorRecords());
        $this->assertEquals(0, count($this->store->getFiles()));
        $this->assertEquals($account->updated, Account::getByUid($account->uid)->updated);
    }

    public function testFail()
    {
        $user = Fixtures::puppet($this->config->domain);
        $account = Fixtures::account($user);
        $account->setAccountData($this->config, Fixtures::accountData());
        $account->updated = new DateTime('-10 min');
        $account->save();

        $imapMock = $this->mock(Imap::class);
        $imapMock->expects($this->once())->method('open')->willThrowException(new Exception('Some error', 500));

        $task = $this->container->get(FetchMail::class);
        $task->run();
        $this->assertTrue($this->logger->hasErrorRecords());
    }
}