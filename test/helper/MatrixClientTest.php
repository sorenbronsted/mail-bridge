<?php

namespace bronsted;

use DateTime;
use Exception;
use stdClass;
use ZBateson\MailMimeParser\Message;

class MatrixClientTest extends TestCase
{
    private AppServiceConfig $config;
    private MatrixClient $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->config = $this->container->get(AppServiceConfig::class);
        $this->mock(Http::class);
        $this->client = $this->container->get(MatrixClient::class);
    }

    public function testCreateUser()
    {
        $mock = $this->container->get(Http::class);
        $mock->method('post')->with($this->stringContains('register'));
        $mock->method('put')->with($this->stringContains('profile'));

        $user = Fixtures::puppet($this->config);
        $ts = new DateTime();
        $this->client->createUser($user, $ts);
        $this->assertTrue(true);
    }

    public function testCreateRoom()
    {
        $fixture = new stdClass();
        $fixture->room_id = 1;

        $mock = $this->container->get(Http::class);
        $mock->method('post')
            ->with($this->stringContains('createRoom'))
            ->willReturn($fixture);

        $user = Fixtures::puppet($this->config);
        $ts = new DateTime();
        $result = $this->client->createRoom('test', 'test', $user);
        $this->assertEquals($fixture->room_id, $result);
    }

    public function testJoin()
    {
        $mock = $this->container->get(Http::class);
        $mock->method('post')->with($this->stringContains('join'));

        $room = Fixtures::room($this->config);
        $user = Fixtures::puppet($this->config);
        $this->client->join($room, $user);
        $this->assertTrue(true);
    }

    public function testInvite()
    {
        $mock = $this->container->get(Http::class);
        $mock->method('post')->with($this->stringContains('invite'));

        $room = Fixtures::room($this->config);
        $user = Fixtures::puppet($this->config);
        $account = Fixtures::account($user);
        $this->client->invite($room, $user, $account);
        $this->assertTrue(true);
    }

    public function testSend()
    {
        $mock = $this->container->get(Http::class);
        $mock->method('put');
        $mock->method('postStream')->willReturn((object)['content_uri' => '/someurl']);

        $room = Fixtures::room($this->config);
        $user = Fixtures::puppet($this->config);
        $ts = new DateTime();
        $message = Message::from(fopen(dirname(__DIR__) .'/data/with_attachment.mime', 'r'), true);
        $this->client->send($room, $user, $message, $ts);
        $this->assertTrue(true);
    }

    public function testHasUser()
    {
        $mock = $this->container->get(Http::class);
        $mock->expects($this->once())->method('get')->willReturn((object)[]);
        $user = Fixtures::puppet($this->config);

        $result = $this->client->hasUser($user);
        $this->assertTrue($result);
    }

    public function testHasNotUser()
    {
        $mock = $this->container->get(Http::class);
        $mock->expects($this->once())->method('get')->willThrowException(new Exception('', 404));
        $user = Fixtures::puppet($this->config);

        $result = $this->client->hasUser($user);
        $this->assertFalse($result);
    }
}