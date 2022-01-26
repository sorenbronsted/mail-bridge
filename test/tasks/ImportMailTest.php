<?php

namespace bronsted;

use Exception;
use ZBateson\MailMimeParser\Header\HeaderConsts;
use ZBateson\MailMimeParser\Message;

class ImportMailTest extends TestCase
{
    private AppServiceConfig $config;
    private FileStore $store;
    private User $user;
    private Account $account;

    protected function setUp():void
    {
        parent::setUp();

        $this->config = $this->container->get(AppServiceConfig::class);
        $this->store = $this->container->get(FileStore::class);

        $this->user = Fixtures::puppet($this->config);
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
        $mail = Fixtures::mailFromFile($this->account, $this->store, 'multi_recipients.mime');
        $message = $mail->getMessage($this->store);
        $name = $message->getHeader(HeaderConsts::SUBJECT)->getValue();
        $name = trim(substr($name, strpos($name, ':') + 1));
        $alias = Room::toAlias($this->config, $name);
        $from = User::fromMail($message->getHeader(HeaderConsts::FROM)->getAddresses()[0], $this->config);
        $ts = $message->getHeader(HeaderConsts::DATE);

        $client = $this->container->get(MatrixClient::class);
        $client->expects($this->atLeastOnce())->method('createUser');
        $client->expects($this->atLeastOnce())->method('invite');
        $client->expects($this->atLeastOnce())->method('join');
        $client->expects($this->once())->method('createRoom')->willReturn('1');
        $client->expects($this->once())->method('getRoomIdByAlias')->willThrowException(new Exception('', 404));
        $client->expects($this->once())->method('send')->with(
            $this->callback(function(Room $item) use($name, $alias) {
                return $item->getId() == '1' &&
                    $item->getName() == $name &&
                    $item->getAlias() == $alias &&
                    count($item->getMembers()) == 22;
            }),
            $this->equalTo($from),
            $this->callback(function(Message $item) use($message) {
                return $item->getTextContent() == $message->getTextContent();
            }),
            $this->equalTo($ts->getDateTime())
        );

        $task = $this->container->get(ImportMail::class);
        $task->run();
        //var_dump($this->logger->records);
        $this->assertFalse($this->logger->hasErrorRecords());
        $this->assertEquals(0, count($this->store->getFiles()));
        $this->assertEquals(0, count(Mail::getAll()));
    }

    public function testRunNoWork()
    {
        $task = $this->container->get(ImportMail::class);
        $task->run();
        $this->assertFalse($this->logger->hasErrorRecords());
        $this->assertEquals(0, count($this->store->getFiles()));
        $this->assertEquals(0, count(Mail::getAll()));
    }

    public function testFail()
    {
        Fixtures::mailFromFile($this->account, $this->store, 'multi_recipients.mime');

        $client = $this->container->get(MatrixClient::class);
        $client->expects($this->once())->method('getRoomIdByAlias')->willThrowException(new Exception('', 404));
        $client->expects($this->once())->method('send')->willThrowException(new Exception('Some error', 17));

        $task = $this->container->get(ImportMail::class);
        $task->run();
        $this->assertTrue($this->logger->hasErrorRecords());

        $mails = Mail::getAll();
        $this->assertEquals(1, count($mails));
        $this->assertNotEquals(0, $mails[0]->fail_code);
    }

    public function testImportMultiUserWithExistingRoom()
    {
        $mail = Fixtures::mailFromFile($this->account, $this->store, 'multi_recipients.mime');
        $message = $mail->getMessage($this->store);
        $name = $message->getHeader(HeaderConsts::SUBJECT)->getValue();
        $client = $this->container->get(MatrixClient::class);
        $room = new Room('1', Room::toAlias($this->config, $name), $name, [Fixtures::puppet($this->config)]);

        $client->expects($this->once())->method('getRoomIdByAlias')->willReturn($room->getId());
        $client->expects($this->once())->method('getRoomName')->willReturn($room->getName());
        $client->expects($this->once())->method('getRoomMembers')->willReturn($room->getMembers());
        $client->expects($this->once())->method('send')->with(
            $this->callback(function(Room $item) use($room) {
                return $item->getId() == $room->getId();
            })
        );

        $task = $this->container->get(ImportMail::class);
        $task->run();
        $this->assertFalse($this->logger->hasErrorRecords());
        $this->assertEquals(0, count(Mail::getAll()));
    }

    public function testImportDirect()
    {
        $mail = Fixtures::mailFromFile($this->account, $this->store, 'direct.mime');
        $message = $mail->getMessage($this->store);
        $from = User::fromMail($message->getHeader(HeaderConsts::FROM)->getAddresses()[0], $this->config);

        $client = $this->container->get(MatrixClient::class);
        $client->expects($this->once())->method('getRoomIdByAlias')->willThrowException(new Exception('', 404));
        $client->expects($this->once())->method('createRoom')->with(
            $this->equalTo($from->getName()),
            $this->equalTo(Room::toAlias($this->config, $from->getId())),
            $this->equalTo($from),
            $this->equalTo(true)
        );

        $task = $this->container->get(ImportMail::class);
        $task->run();
        //var_dump($this->logger->records);
        $this->assertFalse($this->logger->hasErrorRecords());
        $this->assertEquals(0, count(Mail::getAll()));
    }

    public function testImportNoSubject()
    {
        $mail = Fixtures::mailFromFile($this->account, $this->store, 'no_subject.mime');
        $message = $mail->getMessage($this->store);
        $from = User::fromMail($message->getHeader(HeaderConsts::FROM)->getAddresses()[0], $this->config);

        $client = $this->container->get(MatrixClient::class);
        $client->expects($this->once())->method('getRoomIdByAlias')->willThrowException(new Exception('', 404));
        $client->expects($this->once())->method('createRoom')->with(
            $this->stringStartsWith('No subject'),
            $this->stringStartsWith('#no-subject'),
            $this->equalTo($from),
            $this->equalTo(false)
        );

        $task = $this->container->get(ImportMail::class);
        $task->run();
        $this->assertFalse($this->logger->hasErrorRecords());
        $this->assertEquals(0, count(Mail::getAll()));
    }

    public function testImportWithWrongFileNameFormat()
    {
        Fixtures::mailFromFile($this->account, $this->store, 'empty.mime');

        $task = $this->container->get(ImportMail::class);
        $task->run();
        $this->assertTrue($this->logger->hasErrorRecords());

        $mails = Mail::getAll();
        $this->assertEquals(1, count($mails));
        $this->assertNotEquals(0, $mails[0]->fail_code);
    }
}