<?php

namespace bronsted;

use Slim\Psr7\Factory\StreamFactory;
use SplFileInfo;
use stdClass;

class AppServiceCtrlTest extends TestCase
{
    private AppServiceConfig $config;
    private Room $room;
    private User $sender;
    private Account $account;
    private FileStore $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mock(Http::class)
             ->method('getStream')->willReturn((new StreamFactory())->createStream('test'));
        $this->mock(MatrixClient::class);
        $this->config = $this->container->get(AppServiceConfig::class);
        $this->store = $this->container->get(FileStore::class);

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

    public function testLogin()
    {
        $params = new stdClass();
        $params->access_token = $this->config->tokenGuest[0];
        $params->id = Fixtures::user()->getId();

        $req = $this->createRequest('GET', '/account/login?' . http_build_query($params));
        $resp = $this->app->handle($req);
        $this->assertEquals(302, $resp->getStatusCode());
    }

    public function testLoginMissingParameter()
    {
        $req = $this->createRequest('GET', '/account/login');
        $resp = $this->app->handle($req);
        $this->assertEquals(403, $resp->getStatusCode());
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
        $event = Fixtures::event('event_text.json');
        $account = Fixtures::account(User::fromId($event->sender, 'TODO name can be empty'));
        $account->setAccountData($this->config, Fixtures::accountData());
        $account->save();

        $data = new stdClass();
        $data->events = [$event];

        $mock = $this->mock(FileStore::class);
        $mock->expects($this->once())->method('write');

        $req = $this->createJsonRequest('PUT', '/transactions/1?access_token=' . urlencode($this->config->tokenGuest[0]), (array)$data);
        $resp = $this->app->handle($req);
        $this->assertEquals(200, $resp->getStatusCode());
        $this->assertFalse($this->logger->hasErrorRecords());
    }

    public function testEventsSendMessageUrl()
    {
        $event = Fixtures::event('event_url.json');
        $account = Fixtures::account(User::fromId($event->sender, 'TODO name can be empty'));
        $account->setAccountData($this->config, Fixtures::accountData());
        $account->save();

        $data = new stdClass();
        $data->events = [$event];

        $mock = $this->mock(FileStore::class);
        $mock->expects($this->once())->method('write');

        $req = $this->createJsonRequest('PUT', '/transactions/1?access_token=' . urlencode($this->config->tokenGuest[0]), (array)$data);
        $resp = $this->app->handle($req);
        $this->assertEquals(200, $resp->getStatusCode());
        //var_dump( $this->logger->records);
        $this->assertFalse($this->logger->hasErrorRecords());
    }

    public function testUpload()
    {
        $mail = (new StreamFactory())->createStreamFromFile(dirname(__DIR__) . '/data/direct.mime');
        $req = $this->createRequest('POST', '/upload/' . urlencode($this->sender->getId()). '?access_token=' . urlencode($this->config->tokenGuest[0]))
            ->withBody($mail);
        $resp = $this->app->handle($req);
        $this->assertEquals(201, $resp->getStatusCode());
        $mails = Mail::getAll();
        $this->assertEquals(1, count($mails));
        //var_dump( $this->logger->records);
        $this->assertFalse($this->logger->hasErrorRecords());
    }
}
