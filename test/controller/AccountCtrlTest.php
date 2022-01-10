<?php

namespace bronsted;

use Exception;
use stdClass;

class AccountCtrlTest extends TestCase
{

    public function testLoginTokenMissingCredentials()
    {
        $user = Fixtures::user();
        $config = $this->container->get(AppServiceConfig::class);
        $params = new stdClass();
        $params->id = $user->id;

        $req = $this->createRequest('GET', '/account/login/token?' . http_build_query($params));
        $resp = $this->app->handle($req);
        $this->assertEquals(403, $resp->getStatusCode());
    }

    public function testLoginTokenMissingParameter()
    {
        $user = Fixtures::user();
        $config = $this->container->get(AppServiceConfig::class);
        $params = new stdClass();
        $params->access_token = $config->tokenGuest[0];

        $req = $this->createRequest('GET', '/account/login/token?' . http_build_query($params));
        $resp = $this->app->handle($req);
        $this->assertEquals(422, $resp->getStatusCode());
    }

    public function testLogin()
    {
        $user = Fixtures::user();
        $config = $this->container->get(AppServiceConfig::class);
        $params = new stdClass();
        $params->access_token = $config->tokenGuest[0];
        $params->id = $user->id;

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

    public function testIndex()
    {
        $user = Fixtures::user();
        $config = $this->container->get(AppServiceConfig::class);
        $req = $this->createRequest('GET', '/account')->withCookieParams([$config->cookieName => $user->uid]);
        $resp = $this->app->handle($req);
        $this->assertEquals(200, $resp->getStatusCode());
        $resp->getBody()->rewind();
        $this->assertStringContainsString('main', $resp->getBody()->getContents());
    }

    public function testUser()
    {
        $user = Fixtures::user();
        $config = $this->container->get(AppServiceConfig::class);
        $req = $this->createRequest('GET', '/account/user')->withCookieParams([$config->cookieName => $user->uid]);
        $resp = $this->app->handle($req);
        $this->assertEquals(200, $resp->getStatusCode());
        $resp->getBody()->rewind();
        $content = json_decode($resp->getBody()->getContents());
        $this->assertStringContainsString('main', $content->mount);
        $this->assertStringContainsString('Imap url', $content->html);
        $this->assertStringContainsString('disabled', $content->html);
        $this->assertStringContainsString('form-control-plaintext', $content->html);
    }

    public function testVerifyOk()
    {
        $user = Fixtures::user();
        $config = $this->container->get(AppServiceConfig::class);
        $account = Fixtures::account($user);
        $account->setAccountData($config, Fixtures::accountData());
        $account->save();

        $imap = $this->mock(Imap::class);
        $imap->method('canConnect')->willReturn(true);
        $this->container->set(Imap::class, $imap);

        $smtp = $this->mock(Smtp::class);
        $smtp->method('canConnect')->willReturn(true);
        $this->container->set(Smtp::class, $smtp);


        $req = $this->createRequest('GET', '/account/' . $account->uid . '/verify')->withCookieParams([$config->cookieName => $user->uid]);
        $resp = $this->app->handle($req);
        $this->assertEquals(200, $resp->getStatusCode());
        $resp->getBody()->rewind();
        $content = json_decode($resp->getBody()->getContents());
        $this->assertStringContainsString('main', $content->mount);
        $this->assertStringContainsString('Ok', $content->html);
        $this->assertStringContainsString('form-control-plaintext', $content->html);
    }

    public function testVerifyFail()
    {
        $user = Fixtures::user();
        $config = $this->container->get(AppServiceConfig::class);
        $account = Fixtures::account($user);
        $account->setAccountData($config, Fixtures::accountData());
        $account->save();

        $imap = $this->mock(Imap::class);
        $imap->method('canConnect')->willThrowException(new Exception('fail'));
        $this->container->set(Imap::class, $imap);

        $smtp = $this->mock(Smtp::class);
        $smtp->method('canConnect')->willReturn(true);
        $this->container->set(Smtp::class, $smtp);


        $req = $this->createRequest('GET', '/account/' . $account->uid . '/verify')->withCookieParams([$config->cookieName => $user->uid]);
        $resp = $this->app->handle($req);
        $this->assertEquals(200, $resp->getStatusCode());
        $resp->getBody()->rewind();
        $content = json_decode($resp->getBody()->getContents());
        $this->assertStringContainsString('main', $content->mount);
        $this->assertStringContainsString('fail', $content->html);
        $this->assertStringContainsString('form-control', $content->html);
    }

    public function testCreate()
    {
        $user = Fixtures::user();
        $config = $this->container->get(AppServiceConfig::class);
        $req = $this->createRequest('GET', '/account/create')->withCookieParams([$config->cookieName => $user->uid]);
        $resp = $this->app->handle($req);
        $this->assertEquals(200, $resp->getStatusCode());
        $resp->getBody()->rewind();
        $content = json_decode($resp->getBody()->getContents());
        $this->assertStringContainsString('main', $content->mount);
        $this->assertStringContainsString('Smtp', $content->html);
        $this->assertStringNotContainsString('disabled', $content->html);
        $this->assertStringNotContainsString('form-control-plaintext', $content->html);
    }

    public function testShow()
    {
        $config = $this->container->get(AppServiceConfig::class);
        $user = Fixtures::user();
        $imapData = Fixtures::accountData();
        $account = Fixtures::account($user);
        $account->setAccountData($config, $imapData);
        $account->save();

        $req = $this->createRequest('GET', '/account/' . $account->uid . '/show')->withCookieParams([$config->cookieName => $user->uid]);
        $resp = $this->app->handle($req);
        $this->assertEquals(200, $resp->getStatusCode());
        $resp->getBody()->rewind();
        $content = json_decode($resp->getBody()->getContents());
        $this->assertStringContainsString('main', $content->mount);
        $this->assertStringContainsString('disabled', $content->html);
        $this->assertStringContainsString('form-control-plaintext', $content->html);
        $this->assertStringContainsString($imapData->imap_url, $content->html);
        $this->assertStringContainsString($imapData->smtp_host, $content->html);
        $this->assertStringContainsString($imapData->user, $content->html);
        $this->assertStringContainsString($imapData->password, $content->html);
    }

    public function testEdit()
    {
        $config = $this->container->get(AppServiceConfig::class);
        $user = Fixtures::user();
        $imapData = Fixtures::accountData();
        $account = Fixtures::account($user);
        $account->setAccountData($config, $imapData);
        $account->save();

        $req = $this->createRequest('GET', '/account/' . $account->uid . '/edit')->withCookieParams([$config->cookieName => $user->uid]);
        $resp = $this->app->handle($req);
        $this->assertEquals(200, $resp->getStatusCode());
        $resp->getBody()->rewind();
        $content = json_decode($resp->getBody()->getContents());
        $this->assertStringContainsString('main', $content->mount);
        $this->assertStringNotContainsString('disabled', $content->html);
        $this->assertStringNotContainsString('form-control-plaintext', $content->html);
        $this->assertStringContainsString($imapData->imap_url, $content->html);
        $this->assertStringContainsString($imapData->smtp_host, $content->html);
        $this->assertStringContainsString($imapData->user, $content->html);
        $this->assertStringContainsString($imapData->password, $content->html);
    }

    public function testDelete()
    {
        $config = $this->container->get(AppServiceConfig::class);
        $user = Fixtures::user();
        $imapData = Fixtures::accountData();
        $account = Fixtures::account($user);
        $account->setAccountData($config, $imapData);
        $account->save();

        $req = $this->createRequest('GET', '/account/' . $account->uid . '/delete')->withCookieParams([$config->cookieName => $user->uid]);
        $resp = $this->app->handle($req);
        $this->assertEquals(302, $resp->getStatusCode());

        $accounts = Account::getAll();
        $this->assertEquals(0, count($accounts));
    }

    public function testSaveNew()
    {
        $config = $this->container->get(AppServiceConfig::class);
        $user = Fixtures::user();
        $imapData = Fixtures::imapData();
        $imapData->uid = 0;
        $imapData->name = 'test';

        $req = $this->createRequest('POST', '/account/save')->withParsedBody($imapData)->withCookieParams([$config->cookieName => $user->uid]);
        $resp = $this->app->handle($req);
        $this->assertEquals(302, $resp->getStatusCode());

        $accounts = Account::getAll();
        $this->assertEquals(1, count($accounts));
        $this->assertNotEquals($imapData->uid, $accounts[0]->uid);
        $this->assertEquals($imapData->name, $accounts[0]->name);
        $saved = $accounts[0]->getAccountData($config);
        $this->assertEquals($imapData->imap_url, $saved->imap_url);
        $this->assertEquals($imapData->smtp_host, $saved->smtp_host);
        $this->assertEquals($imapData->user, $saved->user);
        $this->assertEquals($imapData->password, $saved->password);
    }

    public function testSaveEdit()
    {
        $config = $this->container->get(AppServiceConfig::class);
        $user = Fixtures::user();
        $account = Fixtures::account($user);
        $account->setAccountData($config, Fixtures::accountData());
        $account->save();

        $fixture = new stdClass();
        $fixture->uid = $account->uid;
        $fixture->name = 'fixture 1';
        $fixture->imap_url = 'fixture 2';
        $fixture->smtp_host = 'fixture 3';
        $fixture->smtp_port = '17';
        $fixture->user = 'fixture 4';
        $fixture->password = 'fixture 5';

        $req = $this->createRequest('POST', '/account/save')->withParsedBody($fixture)->withCookieParams([$config->cookieName => $user->uid]);
        $resp = $this->app->handle($req);
        $this->assertEquals(302, $resp->getStatusCode());

        $accounts = Account::getAll();
        $this->assertEquals(1, count($accounts));
        $this->assertEquals($fixture->uid, $accounts[0]->uid);
        $this->assertEquals($fixture->name, $accounts[0]->name);
        $saved = $accounts[0]->getAccountData($config);
        $this->assertEquals($fixture->imap_url, $saved->imap_url);
        $this->assertEquals($fixture->smtp_host, $saved->smtp_host);
        $this->assertEquals($fixture->user, $saved->user);
        $this->assertEquals($fixture->password, $saved->password);
    }
}