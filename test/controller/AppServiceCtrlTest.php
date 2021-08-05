<?php

namespace bronsted;

use stdClass;

class AppServiceCtrlTest extends TestCase
{
    public function testInvalidCredentials()
    {
        $user = Fixtures::user();
        $req = $this->createRequest('GET', '/users/' . urlencode($user->id) . '?access_token=' . urlencode('not_valid'));
        $this->expectExceptionCode(403);
        $resp = $this->app->handle($req);
    }

    public function testMissingCredentials()
    {
        $user = Fixtures::user();
        $req = $this->createRequest('GET', '/users/' . urlencode($user->id));
        $this->expectExceptionCode(401);
        $resp = $this->app->handle($req);
    }

    public function testHasUserOk()
    {
        $user = Fixtures::user();
        $config = $this->container->get(AppServiceConfig::class);
        $req = $this->createRequest('GET', '/users/' . urlencode($user->id) . '?access_token=' . urlencode($config->tokenGuest));
        $resp = $this->app->handle($req);
        $this->assertEquals(200, $resp->getStatusCode());
        $this->assertJsonData((object)[], $resp);
    }

    public function testHasUserFail()
    {
        $config = $this->container->get(AppServiceConfig::class);
        $req = $this->createRequest('GET', '/users/' . urlencode('@1') . '?access_token=' . urlencode($config->tokenGuest));
        $resp = $this->app->handle($req);
        $this->assertEquals(404, $resp->getStatusCode());
    }

    public function testHasRoomOk()
    {
        $room = Fixtures::room();
        $config = $this->container->get(AppServiceConfig::class);
        $req = $this->createRequest('GET', '/rooms/' . urlencode($room->alias) . '?access_token=' . urlencode($config->tokenGuest));
        $resp = $this->app->handle($req);
        $this->assertEquals(200, $resp->getStatusCode());
        $this->assertJsonData((object)[], $resp);
    }

    public function testHasRoomFail()
    {
        $config = $this->container->get(AppServiceConfig::class);
        $req = $this->createRequest('GET', '/rooms/' . urlencode('unknown') . '?access_token=' . urlencode($config->tokenGuest));
        $resp = $this->app->handle($req);
        $this->assertEquals(404, $resp->getStatusCode());
    }

    public function testHasAccount()
    {
        $user = Fixtures::user();
        $account = Fixtures::account($user);
        $config = $this->container->get(AppServiceConfig::class);
        $account->setContent($config, Fixtures::imapAccount());
        $imap = $this->mock(ImapCtrl::class);
        $imap->method('canConnect');

        $imapData = new stdClass();
        $imapData->imap_url = '1';
        $imapData->smtp_host = '2';
        $imapData->smtp_port = '3';
        $imapData->user = '4';
        $imapData->password = '5';

        $req = $this->createJsonRequest('GET', '/account/' . urlencode($user->id) . '?access_token=' . urlencode($config->tokenGuest), (array)$imapData);
        $resp = $this->app->handle($req);
        $this->assertEquals(200, $resp->getStatusCode());
    }

    public function testHasNotAccount()
    {
        $user = Fixtures::user();
        $imapData = new stdClass();
        $imapData->imap_url = '1';
        $imapData->smtp_host = '2';
        $imapData->smtp_port = '3';
        $imapData->user = '4';
        $imapData->password = '5';

        $config = $this->container->get(AppServiceConfig::class);
        $req = $this->createJsonRequest('GET', '/account/' . urlencode($user->id) . '?access_token=' . urlencode($config->tokenGuest), (array)$imapData);
        $resp = $this->app->handle($req);
        $this->assertEquals(404, $resp->getStatusCode());
    }
    public function testAddAccountOk()
    {
        $user = Fixtures::user();
        $mock = $this->mock(ImapCtrl::class);
        $mock->method('canConnect');
        $this->assertEquals(0, count(Account::getAll()));

        $imapData = new stdClass();
        $imapData->imap_url = '1';
        $imapData->smtp_host = '2';
        $imapData->smtp_port = '3';
        $imapData->user = '4';
        $imapData->password = '5';

        $config = $this->container->get(AppServiceConfig::class);
        $req = $this->createJsonRequest('PUT', '/account/register/' . urlencode($user->id) . '?access_token=' . urlencode($config->tokenGuest), (array)$imapData);
        $resp = $this->app->handle($req);
        $this->assertEquals(200, $resp->getStatusCode());

        $accounts = Account::getAll();
        $this->assertEquals(1, count($accounts));
        $this->assertNotNull(User::getByUid($accounts[0]->user_uid));
        $this->assertNotEmpty($accounts[0]->data);
    }

    public function testEventsCreateRoom()
    {
        $user = new User('Kurt Humbuk', 'kurt@humbuk.dk', 'syntest.lan', 'kurt');
        $user->save();

        $data = new stdClass();
        $data->events = [];

        $event = new stdClass();
        $event->type = 'm.room.create';
        $event->room_id = '1';
        $event->user_id = $user->id;
        $data->events[] = $event;

        $config = $this->container->get(AppServiceConfig::class);
        $req = $this->createJsonRequest('PUT', '/transactions/1?access_token=' . urlencode($config->tokenGuest), (array)$data);
        $resp = $this->app->handle($req);
        $this->assertEquals(200, $resp->getStatusCode());
        $this->assertEquals(1, count(Room::getAll()));
    }

    public function testEventsSetRoomName()
    {
        $user = new User('Kurt Humbuk', 'kurt@humbuk.dk', 'syntest.lan', 'kurt');
        $user->save();
        $room = Fixtures::room();

        $data = new stdClass();
        $data->events = [];

        $event = new stdClass();
        $event->type = 'm.room.name';
        $event->room_id = $room->id;
        $event->content = (object)['name' =>'New name'];
        $event->user_id = $user->id;
        $data->events[] = $event;

        $this->assertNotEquals($room->name, $event->content);

        $config = $this->container->get(AppServiceConfig::class);
        $req = $this->createJsonRequest('PUT', '/transactions/1?access_token=' . urlencode($config->tokenGuest), (array)$data);
        $resp = $this->app->handle($req);
        $this->assertEquals(200, $resp->getStatusCode());

        $room = Room::getByUid($room->uid);
        $this->assertEquals($room->name, $event->content->name);
    }

    public function testEventsRoomMemberWithKnownUser()
    {
        $user = Fixtures::user();
        $room = Fixtures::room();
        $creator = User::getByUid($room->creator_uid);

        $data = new stdClass();
        $data->events = [];

        $event = new stdClass();
        $event->type = 'm.room.member';
        $event->room_id = $room->id;
        $event->content = (object)['membership' =>'invite', 'displayname' => $user->name];
        $event->user_id = $creator->id;
        $event->state_key = $user->id;
        $data->events[] = $event;

        $this->assertEquals(1, count(Member::getAll()));

        $config = $this->container->get(AppServiceConfig::class);
        $req = $this->createJsonRequest('PUT', '/transactions/1?access_token=' . urlencode($config->tokenGuest), (array)$data);
        $resp = $this->app->handle($req);
        $this->assertEquals(200, $resp->getStatusCode());

        $this->assertEquals(2, count(Member::getAll()));
    }

    public function testEventsRoomMemberWithUnKnownUser()
    {
        $room = Fixtures::room();
        $creator = User::getByUid($room->creator_uid);

        $data = new stdClass();
        $data->events = [];

        $event = new stdClass();
        $event->type = 'm.room.member';
        $event->room_id = $room->id;
        $event->content = (object)['membership' =>'invite', 'displayname' => 'Yrsa Humbuk'];
        $event->user_id = $creator->id;
        $event->state_key = '@mail_yrsa/humbuk.dk:syntest.lan';
        $data->events[] = $event;

        $this->assertEquals(1, count(Member::getAll()));

        $config = $this->container->get(AppServiceConfig::class);
        $req = $this->createJsonRequest('PUT', '/transactions/1?access_token=' . urlencode($config->tokenGuest), (array)$data);
        $resp = $this->app->handle($req);
        $this->assertEquals(200, $resp->getStatusCode());

        $this->assertEquals(2, count(Member::getAll()));
    }

    public function testEventsSendMessageText()
    {
        $config = $this->container->get(AppServiceConfig::class);

        $user = Fixtures::user();
        $room = Fixtures::room();
        $room->join($user);
        $sender = User::getByUid($room->creator_uid);
        $account = Fixtures::account($sender);
        $account->setContent($config, Fixtures::imapAccount());
        $account->save();

        $data = new stdClass();
        $data->events = [];

        $event = new stdClass();
        $event->type = 'm.room.message';
        $event->sender = $sender->id;
        $event->room_id = $room->id;
        $event->user_id = $sender->id;
        $event->content = (object)['msgtype' =>'m.text', 'body' => 'hej'];
        $data->events[] = $event;

        $smtp = $this->mock(Smtp::class);
        $smtp->method('open');
        $smtp->method('from');
        $smtp->method('addRecipients');
        $smtp->method('subject');
        $smtp->method('body')->with($this->equalTo('hej'));
        $smtp->method('send');

        $config = $this->container->get(AppServiceConfig::class);
        $req = $this->createJsonRequest('PUT', '/transactions/1?access_token=' . urlencode($config->tokenGuest), (array)$data);
        $resp = $this->app->handle($req);
        $this->assertEquals(200, $resp->getStatusCode());
    }

    public function testEventsSendMessageUrl()
    {
        $config = $this->container->get(AppServiceConfig::class);

        $user = Fixtures::user();
        $room = Fixtures::room();
        $room->join($user);
        $sender = User::getByUid($room->creator_uid);
        $account = Fixtures::account($sender);
        $account->setContent($config, Fixtures::imapAccount());
        $account->save();

        $url = 'mxc://somehwhere.net/me.jpg';
        $data = new stdClass();
        $data->events = [];

        $event = new stdClass();
        $event->type = 'm.room.message';
        $event->sender = $sender->id;
        $event->room_id = $room->id;
        $event->user_id = $sender->id;
        $event->content = (object)['msgtype' =>'m.image', 'url' => $url];
        $data->events[] = $event;

        $smtp = $this->mock(Smtp::class);
        $smtp->method('open');
        $smtp->method('from');
        $smtp->method('addRecipients');
        $smtp->method('subject');
        $smtp->method('body')->with($this->equalTo($url));
        $smtp->method('send');

        $config = $this->container->get(AppServiceConfig::class);
        $req = $this->createJsonRequest('PUT', '/transactions/1?access_token=' . urlencode($config->tokenGuest), (array)$data);
        $resp = $this->app->handle($req);
        $this->assertEquals(200, $resp->getStatusCode());
    }
}