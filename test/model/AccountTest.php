<?php

namespace bronsted;

use Exception;

class AccountTest extends TestCase
{
    private AppServiceConfig $config;

    protected function setUp(): void
    {
        parent::setUp();
        $this->config = $this->container->get(AppServiceConfig::class);
    }

    public function testAccountDataOk()
    {
        $user = Fixtures::puppet($this->config);
        $account = Fixtures::account($user);

        $this->assertEmpty($account->data);
        $this->assertEmpty($account->getAccountData($this->config));

        $fixture = Fixtures::accountData();
        $account->setAccountData($this->config, $fixture);
        $account->save();

        $account = Account::getByUid($account->uid);
        $this->assertNotEmpty($account->data);

        $data = $account->getAccountData($this->config);
        $this->assertNotEmpty($data);
        $this->assertEquals($fixture, $data);
    }

    public function testAccountDataFail()
    {
        $user = Fixtures::puppet($this->config);
        $account = Fixtures::account($user);
        $config = $this->app->getContainer()->get(AppServiceConfig::class);

        $this->assertEmpty($account->data);
        $this->assertEmpty($account->getAccountData($config));

        $fixture = new AccountData();

        $this->expectException(Exception::class);
        $account->setAccountData($config, $fixture);
    }

    public function testVerifyOk()
    {
        $user = Fixtures::puppet($this->config);
        $account = Fixtures::account($user);
        $account->setAccountData($this->config, Fixtures::accountData());

        $this->mock(Imap::class)->method('canConnect')->willReturn(true);
        $this->mock(Smtp::class)->method('canConnect')->willReturn(true);

        $imap = $this->container->get(Imap::class);
        $smtp = $this->container->get(Smtp::class);
        $account->verify($this->config, $imap, $smtp);
        $this->assertEquals(Account::StateOk, $account->state);
    }

    public function testVerifyFail()
    {
        $user = Fixtures::puppet($this->config);
        $account = Fixtures::account($user);
        $config = $this->app->getContainer()->get(AppServiceConfig::class);
        $account->setAccountData($config, Fixtures::accountData());

        $this->mock(Imap::class)->method('canConnect')->willReturn(true);
        $this->mock(Smtp::class)->method('canConnect')->willThrowException(new Exception());

        $imap = $this->container->get(Imap::class);
        $smtp = $this->container->get(Smtp::class);
        $account->verify($config, $imap, $smtp);
        $this->assertEquals(Account::StateFail, $account->state);
    }
}
