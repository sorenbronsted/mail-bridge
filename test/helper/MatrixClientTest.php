<?php

namespace bronsted;

use DateTime;
use stdClass;
use ZBateson\MailMimeParser\Message;

class MatrixClientTest extends TestCase
{
    public function testCreateUser()
    {
        $mock = $this->Mock(Http::class);
        $mock->method('post')->with($this->stringContains('register'));
        $mock->method('put')->with($this->stringContains('profile'));

        $user = Fixtures::user();
        $ts = new DateTime();
        $client = $this->container->get(MatrixClient::class);
        $client->createUser($user, $ts);
        $this->assertTrue(true);
    }

    public function testCreateRoom()
    {
        $fixture = new stdClass();
        $fixture->room_id = 1;

        $mock = $this->Mock(Http::class);
        $mock->method('post')
            ->with($this->stringContains('createRoom'))
            ->willReturn($fixture);

        $user = Fixtures::user();
        $ts = new DateTime();
        $client = $this->container->get(MatrixClient::class);
        $result = $client->createRoom('test', $user, $ts);
        $this->assertEquals($fixture->room_id, $result);
    }

    public function testJoin()
    {
        $mock = $this->Mock(Http::class);
        $mock->method('post')->with($this->stringContains('join'));

        $room = Fixtures::room();
        $user = Fixtures::user();
        $ts = new DateTime();
        $client = $this->container->get(MatrixClient::class);
        $client->join($room, $user, $ts);
        $this->assertTrue(true);
    }

    public function testInvite()
    {
        $mock = $this->Mock(Http::class);
        $mock->method('post')->with($this->stringContains('invite'));

        $room = Fixtures::room();
        $user = Fixtures::user();
        $ts = new DateTime();
        $client = $this->container->get(MatrixClient::class);
        $client->invite($room, $user, $ts);
        $this->assertTrue(true);
    }

    public function testSend()
    {
        $mock = $this->Mock(Http::class);
        $mock->method('put');
        $mock->method('postStream')->willReturn((object)['content_uri' => '/someurl']);

        $room = Fixtures::room();
        $user = Fixtures::user();
        $ts = new DateTime();
        $message = Message::from(file_get_contents(dirname(__DIR__) .'/data/multi_recipients.mime'));
        $client = $this->container->get(MatrixClient::class);
        $client->send($room, $user, $message, $ts);
        $this->assertTrue(true);
    }
}