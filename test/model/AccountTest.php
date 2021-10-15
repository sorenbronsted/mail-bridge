<?php

namespace bronsted;

use stdClass;

class AccountTest extends TestCase
{
    public function testContent()
    {
        $user = Fixtures::user();
        $account = Fixtures::account($user);
        $config = $this->app->getContainer()->get(AppServiceConfig::class);

        $this->assertEmpty($account->data);
        $this->assertEmpty($account->getContent($config));

        $fixture = Fixtures::imapData();
        $account->setContent($config, $fixture);
        $account->save();

        $account = Account::getByUid($account->uid);
        $this->assertNotEmpty($account->data);

        $data = $account->getContent($config);
        $this->assertNotEmpty($data);
        $this->assertEquals($fixture, $data);
    }

    public function testExist()
    {
        $user = new User('Kurt Humbuk', 'me@somewhere.net', 'localhost');
        $this->assertFalse(Account::exists($user));

        $user->save();
        $this->assertNotEmpty($user->id);
        $account = new Account();
        $account->user_uid = $user->uid;
        $account->save();
        $this->assertTrue(Account::exists($user));
    }
}
