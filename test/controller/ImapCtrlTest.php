<?php

namespace bronsted;

use DateTime;
use Exception;
use stdClass;

class ImapCtrlTest extends TestCase
{
    private User $user;
    private Account $account;
    private AppServiceConfig $config;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = Fixtures::user();
        $this->user->id = '1';
        $this->user->save();

        $this->config = $this->container->get(AppServiceConfig::class);
        $this->account = Fixtures::account($this->user);
        $this->account->setAccountData($this->config, Fixtures::accountData());
        $this->account->save();
    }

    public function testFetch()
    {
        $stop = new DateTime();

        $mockFile = $this->mock(File::class);
        $mockFile->method('root');
        $mockFile->method('write');

        $mockMail = $this->mock(Imap::class);
        $mockMail->method('open');
        $mockMail->method('close');
        $mockMail->method('count')->willReturn(5);
        $mockMail->method('header')->willReturn(
            (object)['udate' => $stop->format('U') + 10],
            (object)['udate' => $stop->format('U') - 10],
        );
        $mockMail->method('message')->willReturn('Message 1');

        $ctrl = $this->container->get(ImapCtrl::class);
        $ctrl->fetch($this->account, $stop);
        $this->assertTrue(true);
    }

    public function testSendMessage()
    {
        $smtp = $this->mock(Smtp::class);
        $smtp->method('open');
        $smtp->method('from');
        $smtp->method('addRecipients');
        $smtp->method('subject');
        $smtp->method('body');
        $smtp->method('send');

        $ctrl = $this->container->get(ImapCtrl::class);
        $ctrl->sendMessage($this->user, User::getAll(), 'test', 'test html');
        $this->assertTrue(true);
    }

    public function testImportMultiUser()
    {
        $mock = $this->mock(Http::class);
        $mock->method('post')->willReturn(
            new stdClass(), // register from
            (object)['room_id' => '1'], // create room
            new stdClass(), // register user 1
            new stdClass(), // invite user 1
            new stdClass(), // join user 1
            new stdClass(), // register user 2
            new stdClass(), // invite user 2
            new stdClass(), // join user 2
        );
        $mock->method('put')->willReturn(
            new stdClass(), // register from
            new stdClass(), // register user 1
            new stdClass(), // register user 2
            new stdClass(), // send message
            new stdClass(), // send file
        );
        $mock->method('postStream')->willReturn(
            (object)['content_uri' => 'some url'], // upload
        );

        $fh = fopen(dirname(dirname(__FILE__)) . '/data/with_attachment.mime', 'r');
        $ctrl = $this->container->get(ImapCtrl::class);
        $ctrl->import($this->account, $fh);
        fclose($fh);
        $this->assertEquals(1, count(Room::getAll()));
        $this->assertEquals(4, count(User::getAll()));
    }

    public function testImportDirect()
    {
        // $this->user->email = 'soren@bronsted.dk';
        // $this->user->save();

        $mock = $this->mock(Http::class);
        $mock->method('post')->willReturn(
            new stdClass(), // register from
            (object)['room_id' => '1'], // create room
            new stdClass(), // register user 1
            new stdClass(), // invite user 1
            new stdClass(), // join user 1
        );
        $mock->method('put')->willReturn(
            new stdClass(), // register from
            new stdClass(), // register user 1
            new stdClass(), // send message
        );

        $fh = fopen(dirname(dirname(__FILE__)) . '/data/direct.mime', 'r');
        $ctrl = $this->container->get(ImapCtrl::class);
        $ctrl->import($this->account, $fh);
        fclose($fh);
        $this->assertEquals(1, count(Room::getAll()));
        $this->assertEquals(2, count(User::getAll()));
    }
}