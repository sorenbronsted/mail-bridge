<?php

namespace bronsted;

use Symfony\Component\Mailer\MailerInterface;

class SmtpTest extends TestCase
{
    private MailerInterface $mailer;
    private MailerFactory $factory;
    private Smtp $smtp;
    private AppServiceConfig $config;
    private FileStore $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mailer = $this->mock(MailerInterface::class);
        $this->factory = $this->mock(MailerFactory::class);
        $this->factory->method('create')->willReturn($this->mailer);
        $this->smtp = $this->container->get(Smtp::class);
        $this->config = $this->container->get(AppServiceConfig::class);
        $this->store = $this->container->get(FileStore::class);
    }

    public function testCanConnect()
    {
        $accountData = Fixtures::accountData();
        $this->factory->expects($this->once())->method('create');
        $this->smtp->canConnect($accountData);
    }

    public function testSendSimple()
    {
        $user = Fixtures::puppet($this->config->domain);
        $account = Fixtures::account($user);
        $account->setAccountData($this->config, Fixtures::accountData());
        $account->save();
        $mail = Fixtures::mail($account, $this->store, 'direct.mime');

        $this->mailer->expects($this->once())->method('send');
        $this->smtp->send($mail, $this->config, $this->store);
    }
}