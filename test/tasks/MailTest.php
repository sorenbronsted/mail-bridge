<?php

namespace bronsted;

use DateTime;
use Exception;
use Psr\Log\Test\TestLogger;

class MailTest extends TestCase
{
    private AppServiceConfig $config;
    private FileStore $store;
    private TestLogger $logger;

    protected function setUp():void
    {
        parent::setUp();

        $this->logger = new TestLogger();
        Log::setInstance($this->logger);

        $this->config = $this->container->get(AppServiceConfig::class);
        $this->store = $this->container->get(FileStore::class);
        @unlink($this->config->pidFile);
    }

    public function testNoWork()
    {
        $task = $this->container->get(Mail::class);
        $task->run([]);
        $this->assertFalse($this->logger->hasErrorRecords());
        $this->assertFalse(file_exists($this->config->pidFile));
        $this->assertEmpty(Room::getAll());
        $this->assertEmpty(User::getAll());
        $this->assertEmpty(Account::getAll());
    }

    public function testAllreadyRunning()
    {
        $this->assertFalse(file_exists($this->config->pidFile));
        touch($this->config->pidFile);
        $task = $this->container->get(Mail::class);
        $task->run([]);
        $this->assertTrue($this->logger->hasInfoRecords());
        $this->assertFalse($this->logger->hasErrorRecords());
    }

    public function testLockWriteFail()
    {
        $this->config->pidFile = '/var/run/mailbrige.pid';
        $this->assertFalse(file_exists($this->config->pidFile));
        $task = $this->container->get(Mail::class);
        $task->run([]);
        $this->assertTrue($this->logger->hasErrorRecords());
        $this->assertTrue($this->logger->hasRecordThatContains('Writing lock file failed','error'));
    }

    public function testFetchAndImport()
    {
        $user = Fixtures::user();
        $account = Fixtures::account($user);
        $account->setAccountData($this->config, Fixtures::accountData());
        $account->save();

        $imapMock = $this->mock(Imap::class);
        $imapMock->expects($this->once())->method('open')->withAnyParameters();
        $imapMock->expects($this->once())->method('sort')->withAnyParameters();
        $imapMock->expects($this->once())->method('count')->willReturn(1);
        $imapMock->expects($this->once())->method('header')->willReturn((object)['udate' => (new DateTime())->format('U')]);
        $imapMock->expects($this->once())->method('message')->willReturn(file_get_contents(dirname(__DIR__) . '/data/multi_recipients.mime'));

        $matrixMock = $this->mock(MatrixClient::class);
        $matrixMock->expects($this->once())->method('send');
        $matrixMock->expects($this->atLeastOnce())->method('createUser');
        $matrixMock->expects($this->atLeastOnce())->method('invite');
        $matrixMock->expects($this->once())->method('createRoom')->willReturn('1');


        $task = $this->container->get(Mail::class);
        $task->run([]);
        $this->assertFalse($this->logger->hasErrorRecords());
        $this->assertFalse(file_exists($this->config->pidFile));
        $this->assertEquals(0, count($this->store->getDir(FileStore::Inbox)));
        $this->assertEquals(1, count(Room::getAll()));
        $this->assertEquals(23, count(User::getAll()));
    }

    public function testSend()
    {
        $user = Fixtures::user();
        $account = Fixtures::account($user);
        $account->setAccountData($this->config, Fixtures::accountData());
        $account->save();

        $imapMock = $this->mock(Imap::class);
        $imapMock->expects($this->once())->method('open')->withAnyParameters();
        $imapMock->expects($this->once())->method('sort')->withAnyParameters();
        $imapMock->expects($this->once())->method('count')->willReturn(0);

        $smtpMock = $this->mock(Smtp::class);
        $smtpMock->expects($this->once())->method('sendByAccount');

        $ctrl = $this->container->get(ImapCtrl::class);
        $ctrl->sendMessage($user, User::getAll(), 'Subject', Fixtures::event());

        $task = $this->container->get(Mail::class);
        $task->run([]);
        $this->assertFalse($this->logger->hasErrorRecords());
        $this->assertFalse(file_exists($this->config->pidFile));
        $this->assertEquals(0, count($this->store->getDir(FileStore::Outbox)));
    }

    public function testFetchFail()
    {
        $user = Fixtures::user();
        $account = Fixtures::account($user);
        $account->setAccountData($this->config, Fixtures::accountData());
        $account->save();

        $imapMock = $this->mock(Imap::class);
        $imapMock->expects($this->once())->method('open')->willThrowException(new Exception('Some error'));

        $task = $this->container->get(Mail::class);
        $task->run([]);
        $this->assertTrue($this->logger->hasErrorRecords());
    }

    public function testImportFail()
    {
        $user = Fixtures::user();
        $account = Fixtures::account($user);
        $account->setAccountData($this->config, Fixtures::accountData());
        $account->save();

        $imapMock = $this->mock(Imap::class);
        $imapMock->expects($this->once())->method('open')->withAnyParameters();
        $imapMock->expects($this->once())->method('sort')->withAnyParameters();
        $imapMock->expects($this->once())->method('count')->willReturn(1);
        $imapMock->expects($this->once())->method('header')->willReturn((object)['udate' => (new DateTime())->format('U')]);
        $imapMock->expects($this->once())->method('message')->willReturn(file_get_contents(dirname(__DIR__) . '/data/multi_recipients.mime'));

        $matrixMock = $this->mock(MatrixClient::class);
        $matrixMock->expects($this->once())->method('send')->willThrowException(new Exception('Some error'));

        $task = $this->container->get(Mail::class);
        $task->run([]);
        $this->assertTrue($this->logger->hasErrorThatContains('Some error'));
        $this->assertEquals(0, count($this->store->getDir(FileStore::Inbox)));
        $this->assertEquals(1, count($this->store->getDir(FileStore::FailImport)));
    }

    public function testSendFail()
    {
        $user = Fixtures::user();
        $account = Fixtures::account($user);
        $account->setAccountData($this->config, Fixtures::accountData());
        $account->save();

        $imapMock = $this->mock(Imap::class);
        $imapMock->expects($this->once())->method('open')->withAnyParameters();
        $imapMock->expects($this->once())->method('sort')->withAnyParameters();
        $imapMock->expects($this->once())->method('count')->willReturn(0);

        $smtpMock = $this->mock(Smtp::class);
        $smtpMock->expects($this->once())->method('sendByAccount')->willThrowException(new Exception('Some error'));

        $ctrl = $this->container->get(ImapCtrl::class);
        $ctrl->sendMessage($user, User::getAll(), 'Subject', Fixtures::event());

        $task = $this->container->get(Mail::class);
        $task->run([]);
        $this->assertTrue($this->logger->hasErrorThatContains('Some error'));
        $this->assertEquals(0, count($this->store->getDir(FileStore::Outbox)));
        $this->assertEquals(1, count($this->store->getDir(FileStore::FailSend)));
    }
}