<?php

namespace bronsted;

use DateTime;
use DirectoryIterator;
use Exception;
use SplFileInfo;
use stdClass;
use ZBateson\MailMimeParser\Message;

class ImapCtrlTest extends TestCase
{
    private User $user;
    private Account $account;
    private AppServiceConfig $config;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = Fixtures::user();

        $this->config = $this->container->get(AppServiceConfig::class);
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

    public function testFetch()
    {
        $mockFile = $this->mock(FileStore::class);
        $mockFile->method('write')->with(
            $this->equalTo(FileStore::Inbox),
            $this->stringStartsWith($this->account->uid . '-')
        );

        $mockMail = $this->mock(Imap::class);
        $mockMail->method('open');
        $mockMail->method('close');
        $mockMail->method('count')->willReturn(5);
        $mockMail->method('header')->willReturn(
            (object)['udate' => (new DateTime())->format('U')],
            (object)['udate' => (new DateTime('yesterday'))->format('U') - 10],
        );
        $mockMail->method('message')->willReturn('Message 1');

        $ctrl = $this->container->get(ImapCtrl::class);
        $ctrl->fetch($this->account);
        $this->assertTrue(true);
    }

    public function testSendMessage()
    {
        $mock = $this->mock(FileStore::class);
        $mock->expects($this->once())->method('write');

        $ctrl = $this->container->get(ImapCtrl::class);
        $ctrl->sendMessage($this->user, User::getAll(), 'Subject', Fixtures::event());
    }

    public function testSend()
    {
        $mock = $this->mock(Smtp::class);
        $mock->expects($this->atLeastOnce())->method('sendByAccount');

        $ctrl = $this->container->get(ImapCtrl::class);
        $ctrl->sendMessage($this->user, User::getAll(), 'Subject', Fixtures::event());

        $store = $this->container->get(FileStore::class);
        $items = $store->getDir(FileStore::Outbox);
        foreach($items as $item) {
            $ctrl->send($item);
        }
    }

    public function testImportMultiUserWithNonExistingRoom()
    {
        $fixture = dirname(dirname(__FILE__)) . '/data/with_attachment.mime';
        $filename = '/tmp/' . $this->account->uid . '-' . uniqid() . '.mime';
        copy($fixture, $filename);
        $file = new SplFileInfo($filename);
        $ctrl = $this->container->get(ImapCtrl::class);
        $ctrl->import($file);
        $this->assertEquals(1, count(Room::getAll()));
        $this->assertEquals(4, count(User::getAll()));
    }

    public function testImportMultiUserWithExistingRoom()
    {
        $room = Fixtures::room();
        $room->name = 'Båd Nyt';
        $room->save();

        $fixture = dirname(dirname(__FILE__)) . '/data/multi_recipients.mime';
        $filename = '/tmp/' . $this->account->uid . '-' . uniqid() . '.mime';
        copy($fixture, $filename);
        $file = new SplFileInfo($filename);
        $ctrl = $this->container->get(ImapCtrl::class);
        $ctrl->import($file);
        $this->assertEquals(1, count(Room::getAll()));
        $this->assertEquals(24, count(User::getAll()));
    }

    public function testImportDirect()
    {
        $fixture = dirname(dirname(__FILE__)) . '/data/direct.mime';
        $filename = '/tmp/' . $this->account->uid . '-' . uniqid() . '.mime';
        copy($fixture, $filename);
        $file = new SplFileInfo($filename);
        $ctrl = $this->container->get(ImapCtrl::class);
        $ctrl->import($file);
        $this->assertEquals(1, count(Room::getAll()));
        $this->assertEquals(2, count(User::getAll()));
    }

    public function testImportNoSubject()
    {
        $fixture = dirname(dirname(__FILE__)) . '/data/no_subject.mime';
        $filename = '/tmp/' . $this->account->uid . '-' . uniqid() . '.mime';
        copy($fixture, $filename);
        $file = new SplFileInfo($filename);
        $ctrl = $this->container->get(ImapCtrl::class);
        $ctrl->import($file);
        $rooms = Room::getAll();
        $this->assertEquals(1, count($rooms));
        $this->assertStringContainsString('No subject', $rooms[0]->name);
        $this->assertEquals(23, count(User::getAll()));
    }

    public function testImportReply()
    {
        $fixture = dirname(dirname(__FILE__)) . '/data/reply.mime';
        $filename = '/tmp/' . $this->account->uid . '-' . uniqid() . '.mime';
        copy($fixture, $filename);
        $file = new SplFileInfo($filename);
        $ctrl = $this->container->get(ImapCtrl::class);
        $ctrl->import($file);

        $rooms = Room::getAll();
        $this->assertEquals(1, count($rooms));
        $this->assertEquals('Båd Nyt', $rooms[0]->name);
        $this->assertEquals(23, count(User::getAll()));
    }

    public function testImportWithWrongFileNameFormat()
    {
        $filename = '/tmp/' . uniqid() . '.mime';
        $ctrl = $this->container->get(ImapCtrl::class);
        $this->expectException(Exception::class);
        $ctrl->import(new SplFileInfo($filename));
    }
}