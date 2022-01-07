<?php

namespace bronsted;

use Exception;
use Psr\Log\Test\TestLogger;

class ImportMailTest extends TestCase
{
    private AppServiceConfig $config;
    private FileStore $store;
    private TestLogger $logger;
    private User $user;
    private Account $account;

    protected function setUp():void
    {
        parent::setUp();

        $this->logger = new TestLogger();
        Log::setInstance($this->logger);

        $this->config = $this->container->get(AppServiceConfig::class);
        $this->store = $this->container->get(FileStore::class);

        $this->user = Fixtures::user();
        $this->account = Fixtures::account($this->user);
        $this->account->setAccountData($this->config, Fixtures::accountData());
        $this->account->save();

        $mock = $this->mock(MatrixClient::class);
        $mock->method('send');
        $mock->method('createUser');
        $mock->method('invite');
        $mock->method('join');
        $mock->method('upload');
        $mock->method('createRoom')->willReturn('1');

    }

    public function testRunDefault()
    {
        Fixtures::mail($this->account, $this->store, 'multi_recipients.mime');

        $matrixMock = $this->container->get(MatrixClient::class);
        $matrixMock->expects($this->once())->method('send');
        $matrixMock->expects($this->atLeastOnce())->method('createUser');
        $matrixMock->expects($this->atLeastOnce())->method('invite');
        $matrixMock->expects($this->once())->method('createRoom')->willReturn('1');

        $task = $this->container->get(ImportMail::class);
        $task->run();
        $this->assertFalse($this->logger->hasErrorRecords());
        $this->assertEquals(0, count($this->store->getFiles()));
        $this->assertEquals(1, count(Room::getAll()));
        $this->assertEquals(23, count(User::getAll()));
        $this->assertEquals(0, count(Mail::getAll()));
    }

    public function testRunNoWork()
    {
        $task = $this->container->get(ImportMail::class);
        $task->run();
        $this->assertFalse($this->logger->hasErrorRecords());
        $this->assertEquals(0, count($this->store->getFiles()));
        $this->assertEquals(0, count(Room::getAll()));
        $this->assertEquals(1, count(User::getAll()));
        $this->assertEquals(0, count(Mail::getAll()));
    }

    public function testFail()
    {
        Fixtures::mail($this->account, $this->store, 'multi_recipients.mime');

        $matrixMock = $this->container->get(MatrixClient::class);
        $matrixMock->expects($this->once())->method('send')->willThrowException(new Exception('Some error', 17));

        $task = $this->container->get(ImportMail::class);
        $task->run();
        $this->assertTrue($this->logger->hasErrorRecords());

        $mails = Mail::getAll();
        $this->assertEquals(1, count($mails));
        $this->assertNotEquals(0, $mails[0]->fail_code);
    }

    public function testImportMultiUserWithNonExistingRoom()
    {
        Fixtures::mail($this->account, $this->store, 'with_attachment.mime');

        $task = $this->container->get(ImportMail::class);
        $task->run();
        $this->assertFalse($this->logger->hasErrorRecords());
        $this->assertEquals(0, count(Mail::getAll()));
        $this->assertEquals(1, count(Room::getAll()));
        $this->assertEquals(4, count(User::getAll()));
    }

    public function testImportMultiUserWithExistingRoom()
    {
        $room = Fixtures::room();
        $room->name = 'Båd Nyt';
        $room->save();
        Fixtures::mail($this->account, $this->store, 'multi_recipients.mime');

        $task = $this->container->get(ImportMail::class);
        $task->run();
        $this->assertFalse($this->logger->hasErrorRecords());
        $this->assertEquals(0, count(Mail::getAll()));
        $this->assertEquals(1, count(Room::getAll()));
        $this->assertEquals(24, count(User::getAll()));
    }

    public function testImportDirect()
    {
        Fixtures::mail($this->account, $this->store, 'direct.mime');

        $task = $this->container->get(ImportMail::class);
        $task->run();
        $this->assertFalse($this->logger->hasErrorRecords());
        $this->assertEquals(0, count(Mail::getAll()));
        $this->assertEquals(1, count(Room::getAll()));
        $this->assertEquals(2, count(User::getAll()));
    }

    public function testImportNoSubject()
    {
        Fixtures::mail($this->account, $this->store, 'no_subject.mime');

        $task = $this->container->get(ImportMail::class);
        $task->run();
        $this->assertFalse($this->logger->hasErrorRecords());
        $this->assertEquals(0, count(Mail::getAll()));

        $rooms = Room::getAll();
        $this->assertEquals(1, count($rooms));
        $this->assertStringContainsString('No subject', $rooms[0]->name);
        $this->assertEquals(23, count(User::getAll()));
    }

    public function testImportReply()
    {
        Fixtures::mail($this->account, $this->store, 'reply.mime');

        $task = $this->container->get(ImportMail::class);
        $task->run();
        $this->assertFalse($this->logger->hasErrorRecords());
        $this->assertEquals(0, count(Mail::getAll()));

        $rooms = Room::getAll();
        $this->assertEquals(1, count($rooms));
        $this->assertEquals('Båd Nyt', $rooms[0]->name);
        $this->assertEquals(23, count(User::getAll()));
    }

    public function testImportWithWrongFileNameFormat()
    {
        Fixtures::mail($this->account, $this->store, 'empty.mime');

        $task = $this->container->get(ImportMail::class);
        $task->run();
        $this->assertTrue($this->logger->hasErrorRecords());

        $mails = Mail::getAll();
        $this->assertEquals(1, count($mails));
        $this->assertNotEquals(0, $mails[0]->fail_code);
    }
}