<?php

namespace bronsted;

use Exception;

class AccountTest extends TestCase
{
    public function testAccountDataOk()
    {
        $user = Fixtures::user();
        $account = Fixtures::account($user);
        $config = $this->app->getContainer()->get(AppServiceConfig::class);

        $this->assertEmpty($account->data);
        $this->assertEmpty($account->getAccountData($config));

        $fixture = Fixtures::accountData();
        $account->setAccountData($config, $fixture);
        $account->save();

        $account = Account::getByUid($account->uid);
        $this->assertNotEmpty($account->data);

        $data = $account->getAccountData($config);
        $this->assertNotEmpty($data);
        $this->assertEquals($fixture, $data);
    }

    public function testAccountDataFail()
    {
        $user = Fixtures::user();
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
        $user = Fixtures::user();
        $account = Fixtures::account($user);
        $config = $this->app->getContainer()->get(AppServiceConfig::class);
        $account->setAccountData($config, Fixtures::accountData());

        $imap = $this->mock(Imap::class);
        $imap->method('canConnect')->willReturn(true);
        $smtp = $this->mock(Smtp::class);
        $smtp->method('canConnect')->willReturn(true);

        $account->verify($config, $imap, $smtp);
        $this->assertEquals(Account::StateOk, $account->state);
    }

    public function testVerifyFail()
    {
        $user = Fixtures::user();
        $account = Fixtures::account($user);
        $config = $this->app->getContainer()->get(AppServiceConfig::class);
        $account->setAccountData($config, Fixtures::accountData());

        $imap = $this->mock(Imap::class);
        $imap->method('canConnect')->willReturn(true);
        $smtp = $this->mock(Smtp::class);
        $smtp->method('canConnect')->willThrowException(new Exception());

        $account->verify($config, $imap, $smtp);
        $this->assertEquals(Account::StateFail, $account->state);
    }
}
