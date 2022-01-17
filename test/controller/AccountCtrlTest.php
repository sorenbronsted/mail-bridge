<?php

namespace bronsted;

use Exception;
use stdClass;

class AccountCtrlTest extends TestCase
{
    private User $user;
    private AppServiceConfig $config;

    protected function setUp(): void
    {
        parent::setUp();
        $this->config = $this->container->get(AppServiceConfig::class);
        $this->user = Fixtures::user();
    }

    public function testIndex()
    {
        $req = $this->createRequest('GET', '/account')->withCookieParams([$this->config->cookieName => $this->user->getId()]);
        $resp = $this->app->handle($req);
        $this->assertEquals(200, $resp->getStatusCode());
        $resp->getBody()->rewind();
        $this->assertStringContainsString('main', $resp->getBody()->getContents());
    }

    public function testUser()
    {
        $req = $this->createRequest('GET', '/account/user')->withCookieParams([$this->config->cookieName => $this->user->getId()]);
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
        $account = Fixtures::account($this->user);
        $account->setAccountData($this->config, Fixtures::accountData());
        $account->save();

        $imap = $this->mock(Imap::class);
        $imap->method('canConnect')->willReturn(true);
        $this->container->set(Imap::class, $imap);

        $smtp = $this->mock(Smtp::class);
        $smtp->method('canConnect')->willReturn(true);
        $this->container->set(Smtp::class, $smtp);


        $req = $this->createRequest('GET', '/account/' . $account->uid . '/verify')->withCookieParams([$this->config->cookieName => $this->user->getId()]);
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
        $account = Fixtures::account($this->user);
        $account->setAccountData($this->config, Fixtures::accountData());
        $account->save();

        $imap = $this->mock(Imap::class);
        $imap->method('canConnect')->willThrowException(new Exception('fail'));
        $this->container->set(Imap::class, $imap);

        $smtp = $this->mock(Smtp::class);
        $smtp->method('canConnect')->willReturn(true);
        $this->container->set(Smtp::class, $smtp);


        $req = $this->createRequest('GET', '/account/' . $account->uid . '/verify')->withCookieParams([$this->config->cookieName => $this->user->getId()]);
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
        $req = $this->createRequest('GET', '/account/create')->withCookieParams([$this->config->cookieName => $this->user->getId()]);
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
        $imapData = Fixtures::accountData();
        $account = Fixtures::account($this->user);
        $account->setAccountData($this->config, $imapData);
        $account->save();

        $req = $this->createRequest('GET', '/account/' . $account->uid . '/show')->withCookieParams([$this->config->cookieName => $this->user->getId()]);
        $resp = $this->app->handle($req);
        $this->assertEquals(200, $resp->getStatusCode());
        $resp->getBody()->rewind();
        $content = json_decode($resp->getBody()->getContents());
        $this->assertStringContainsString('main', $content->mount);
        $this->assertStringContainsString('disabled', $content->html);
        $this->assertStringContainsString('form-control-plaintext', $content->html);
        $this->assertStringContainsString($imapData->imap_url, $content->html);
        $this->assertStringContainsString($imapData->smtp_host, $content->html);
        $this->assertStringContainsString($imapData->email, $content->html);
        $this->assertStringContainsString($imapData->user_name, $content->html);
        $this->assertStringContainsString($imapData->password, $content->html);
    }

    public function testEdit()
    {
        $imapData = Fixtures::accountData();
        $account = Fixtures::account($this->user);
        $account->setAccountData($this->config, $imapData);
        $account->save();

        $req = $this->createRequest('GET', '/account/' . $account->uid . '/edit')->withCookieParams([$this->config->cookieName => $this->user->getId()]);
        $resp = $this->app->handle($req);
        $this->assertEquals(200, $resp->getStatusCode());
        $resp->getBody()->rewind();
        $content = json_decode($resp->getBody()->getContents());
        $this->assertStringContainsString('main', $content->mount);
        $this->assertStringNotContainsString('disabled', $content->html);
        $this->assertStringNotContainsString('form-control-plaintext', $content->html);
        $this->assertStringContainsString($imapData->imap_url, $content->html);
        $this->assertStringContainsString($imapData->smtp_host, $content->html);
        $this->assertStringContainsString($imapData->email, $content->html);
        $this->assertStringContainsString($imapData->user_name, $content->html);
        $this->assertStringContainsString($imapData->password, $content->html);
    }

    public function testDelete()
    {
        $imapData = Fixtures::accountData();
        $account = Fixtures::account($this->user);
        $account->setAccountData($this->config, $imapData);
        $account->save();

        $req = $this->createRequest('GET', '/account/' . $account->uid . '/delete')->withCookieParams([$this->config->cookieName => $this->user->getId()]);
        $resp = $this->app->handle($req);
        $this->assertEquals(302, $resp->getStatusCode());

        $accounts = Account::getAll();
        $this->assertEquals(0, count($accounts));
    }

    public function testSaveNew()
    {
        $imapData = Fixtures::imapData();
        $imapData->uid = 0;
        $imapData->name = 'test';

        $req = $this->createRequest('POST', '/account/save')->withParsedBody($imapData)->withCookieParams([$this->config->cookieName => $this->user->getId()]);
        $resp = $this->app->handle($req);
        $this->assertEquals(302, $resp->getStatusCode());

        $accounts = Account::getAll();
        $this->assertEquals(1, count($accounts));
        $this->assertNotEquals($imapData->uid, $accounts[0]->uid);
        $this->assertEquals($imapData->name, $accounts[0]->name);
        $saved = $accounts[0]->getAccountData($this->config);
        $this->assertEquals($imapData->imap_url, $saved->imap_url);
        $this->assertEquals($imapData->smtp_host, $saved->smtp_host);
        $this->assertEquals($imapData->smtp_port, $saved->smtp_port);
        $this->assertEquals($imapData->email, $saved->email);
        $this->assertEquals($imapData->user_name, $saved->user_name);
        $this->assertEquals($imapData->password, $saved->password);
    }

    public function testSaveEdit()
    {
        $account = Fixtures::account($this->user);
        $account->setAccountData($this->config, Fixtures::accountData());
        $account->save();

        $fixture = new stdClass();
        $fixture->uid = $account->uid;
        $fixture->name = 'fixture 1';
        $fixture->imap_url = 'fixture 2';
        $fixture->smtp_host = 'fixture 3';
        $fixture->smtp_port = '17';
        $fixture->email = 'me@nowhere.lan';
        $fixture->user_name = 'fixture 5';
        $fixture->password = 'fixture 6';

        $req = $this->createRequest('POST', '/account/save')->withParsedBody($fixture)->withCookieParams([$this->config->cookieName => $this->user->getId()]);
        $resp = $this->app->handle($req);
        $this->assertEquals(302, $resp->getStatusCode());

        $accounts = Account::getAll();
        $this->assertEquals(1, count($accounts));
        $this->assertEquals($fixture->uid, $accounts[0]->uid);
        $this->assertEquals($fixture->name, $accounts[0]->name);
        $saved = $accounts[0]->getAccountData($this->config);
        $this->assertEquals($fixture->imap_url, $saved->imap_url);
        $this->assertEquals($fixture->smtp_host, $saved->smtp_host);
        $this->assertEquals($fixture->smtp_port, $saved->smtp_port);
        $this->assertEquals($fixture->email, $saved->email);
        $this->assertEquals($fixture->user_name, $saved->user_name);
        $this->assertEquals($fixture->password, $saved->password);
    }
}