<?php

namespace bronsted;

use Slim\Psr7\Factory\StreamFactory;
use stdClass;

class AppServiceCtrlTest extends TestCase
{
    private AppServiceConfig $config;
    private Room $room;
    private User $sender;
    private Account $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mock(Http::class)
            ->method('getStream')->willReturn((new StreamFactory())->createStream('test'));
        $this->mock(MatrixClient::class);
        $this->config = $this->container->get(AppServiceConfig::class);

        $client = $this->container->get(MatrixClient::class);
        $this->room = Fixtures::room($client, $this->config->domain);
        $client->method('getRoomName')->willReturn($this->room->getName());
        $client->method('getRoomAlias')->willReturn($this->room->getAlias());
        $client->method('getRoomMembers')->willReturn($this->room->getMembers());

        $this->sender = new User('@foo:bar.com', 'Foo Bar');
        $this->account = Fixtures::account($this->sender);
        $this->account->setAccountData($this->config, Fixtures::accountData());
        $this->account->save();
    }

    public function testLoginTokenMissingCredentials()
    {
        $params = new stdClass();
        $params->id = Fixtures::user()->getId();

        $req = $this->createRequest('GET', '/account/login/token?' . http_build_query($params));
        $resp = $this->app->handle($req);
        $this->assertEquals(403, $resp->getStatusCode());
    }

    public function testLoginTokenMissingParameter()
    {
        $params = new stdClass();
        $params->access_token = $this->config->tokenGuest[0];

        $req = $this->createRequest('GET', '/account/login/token?' . http_build_query($params));
        $resp = $this->app->handle($req);
        $this->assertEquals(422, $resp->getStatusCode());
    }

    public function testLogin()
    {
        $params = new stdClass();
        $params->access_token = $this->config->tokenGuest[0];
        $params->id = Fixtures::user()->getId();

        $req = $this->createRequest('GET', '/account/login/token?' . http_build_query($params));
        $resp = $this->app->handle($req);
        $this->assertEquals(200, $resp->getStatusCode());
        $resp->getBody()->rewind();
        $result = json_decode($resp->getBody());
        $this->assertNotEmpty($result->token);

        $params = new stdClass();
        $params->token = $result->token;

        $req = $this->createRequest('GET', '/account/login?' . http_build_query($params));
        $resp = $this->app->handle($req);
        $this->assertEquals(302, $resp->getStatusCode());
    }

    public function testLoginMissingParameter()
    {
        $req = $this->createRequest('GET', '/account/login');
        $resp = $this->app->handle($req);
        $this->assertEquals(422, $resp->getStatusCode());
    }


    public function testInvalidCredentials()
    {
        $req = $this->createRequest('PUT', '/transactions/1?access_token=' . urlencode('not_valid'));
        $resp = $this->app->handle($req);
        $this->assertEquals(401, $resp->getStatusCode());
        $this->assertFalse($this->logger->hasErrorRecords());
    }

    public function testMissingCredentials()
    {
        $req = $this->createRequest('PUT', '/transactions/1');
        $resp = $this->app->handle($req);
        $this->assertEquals(403, $resp->getStatusCode());
        $this->assertFalse($this->logger->hasErrorRecords());
    }

    public function testMessageTextEvent()
    {
        $data = new stdClass();
        $data->events = [];

        $event = new stdClass();
        $event->event_id = '1';
        $event->type = 'm.room.message';
        $event->sender = $this->sender->getId();
        $event->room_id = $this->room->getId();
        $event->user_id = $this->sender->getId();
        $event->content = (object)['msgtype' => 'm.text', 'body' => 'hej'];
        $data->events[] = $event;

        $mock = $this->mock(FileStore::class);
        $mock->expects($this->once())->method('write');

        $config = $this->container->get(AppServiceConfig::class);
        $req = $this->createJsonRequest('PUT', '/transactions/1?access_token=' . urlencode($config->tokenGuest[0]), (array)$data);
        $resp = $this->app->handle($req);
        $this->assertEquals(200, $resp->getStatusCode());
        $this->assertFalse($this->logger->hasErrorRecords());
    }

    public function testEventsSendMessageUrl()
    {
        $url = 'mxc://nowhere/me.jpg';
        $data = new stdClass();
        $data->events = [];

        $event = new stdClass();
        $event->event_id = '1';
        $event->type = 'm.room.message';
        $event->sender = $this->sender->getId();
        $event->room_id = $this->room->getId();
        $event->user_id = $this->sender->getId();
        $event->content = (object)['msgtype' => 'm.image', 'url' => $url];
        $data->events[] = $event;

        $mock = $this->mock(FileStore::class);
        $mock->expects($this->once())->method('write');

        $config = $this->container->get(AppServiceConfig::class);
        $req = $this->createJsonRequest('PUT', '/transactions/1?access_token=' . urlencode($config->tokenGuest[0]), (array)$data);
        $resp = $this->app->handle($req);
        $this->assertEquals(200, $resp->getStatusCode());
        //var_dump( $this->logger->records);
        $this->assertFalse($this->logger->hasErrorRecords());
    }
}
