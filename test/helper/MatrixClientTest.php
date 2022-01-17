<?php

namespace bronsted;

use DateTime;
use stdClass;
use ZBateson\MailMimeParser\Message;

class MatrixClientTest extends TestCase
{
    private AppServiceConfig $config;

    protected function setUp(): void
    {
        parent::setUp();
        $this->config = $this->container->get(AppServiceConfig::class);
    }

    public function testCreateUser()
    {
        $mock = $this->Mock(Http::class);
        $mock->method('post')->with($this->stringContains('register'));
        $mock->method('put')->with($this->stringContains('profile'));

        $user = Fixtures::puppet($this->config->domain);
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

        $user = Fixtures::puppet($this->config->domain);
        $ts = new DateTime();
        $client = $this->container->get(MatrixClient::class);
        $result = $client->createRoom('test', 'test', $user);
        $this->assertEquals($fixture->room_id, $result);
    }

    public function testJoin()
    {
        $mock = $this->Mock(Http::class);
        $mock->method('post')->with($this->stringContains('join'));
        $client = $this->container->get(MatrixClient::class);

        $room = Fixtures::room($client, $this->config->domain);
        $user = Fixtures::puppet($this->config->domain);
        $client->join($room, $user);
        $this->assertTrue(true);
    }

    public function testInvite()
    {
        $mock = $this->Mock(Http::class);
        $mock->method('post')->with($this->stringContains('invite'));
        $client = $this->container->get(MatrixClient::class);

        $room = Fixtures::room($client, $this->config->domain);
        $user = Fixtures::puppet($this->config->domain);
        $account = Fixtures::account($user);
        $client->invite($room, $user, $account);
        $this->assertTrue(true);
    }

    public function testSend()
    {
        $mock = $this->Mock(Http::class);
        $mock->method('put');
        $mock->method('postStream')->willReturn((object)['content_uri' => '/someurl']);
        $client = $this->container->get(MatrixClient::class);

        $room = Fixtures::room($client, $this->config->domain);
        $user = Fixtures::puppet($this->config->domain);
        $ts = new DateTime();
        $message = Message::from(fopen(dirname(__DIR__) .'/data/with_attachment.mime', 'r'), true);
        $client->send($room, $user, $message, $ts);
        $this->assertTrue(true);
    }
}