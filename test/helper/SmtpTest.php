<?php

namespace bronsted;

use Symfony\Component\Mailer\MailerInterface;

class SmtpTest extends TestCase
{
    private $mailer;
    private $factory;
    private Smtp $smtp;
    private AppServiceConfig $config;
    private FileStore $store;
    private $client;
    private Http $http;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = $this->mock(MatrixClient::class);

        $this->mailer = $this->mock(MailerInterface::class);
        $this->factory = $this->mock(MailerFactory::class);
        $this->factory->method('create')->willReturn($this->mailer);
        $this->smtp = $this->container->get(Smtp::class);
        $this->config = $this->container->get(AppServiceConfig::class);
        $this->store = $this->container->get(FileStore::class);
        $this->http = $this->container->get(Http::class);
    }

    public function testCanConnect()
    {
        $accountData = Fixtures::accountData();
        $this->factory->expects($this->once())->method('create');
        $this->smtp->canConnect($accountData);
    }

    public function testSendSimple()
    {
        $event = Fixtures::event('event_text.json');
        $user = User::fromId($event->sender, 'TODO fixme');
        $account = Fixtures::account($user);
        $account->setAccountData($this->config, Fixtures::accountData());
        $account->save();

        $this->client->method('getRoomName')->willReturn('Some name');
        $this->client->method('getRoomAlias')->willReturn('some-alias');
        $this->client->method('getRoomMembers')->willReturn([Fixtures::puppet($this->config->domain)]);
        $mail = Fixtures::mailFromEvent($this->client, $this->config, $this->http, $this->store, $event);


        $this->mailer->expects($this->once())->method('send');
        $this->smtp->send($mail, $this->config, $this->store);
    }
}