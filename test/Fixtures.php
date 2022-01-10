<?php

namespace bronsted;

use stdClass;

class Fixtures
{
    public static function clean()
    {
        $con = Db::getConnection();
        $tables = ['user', 'room', 'member', 'account', 'mail'];
        foreach($tables as $table) {
            $con->execute("delete from $table");
        }
    }

    public static function user(): User
    {
        $user = new User('Kurt Humbuk', 'kurt@humbuk.net', 'syn.lan');
        $user->save();
        return $user;
    }

    public static function room(): Room
    {
        $creator = new User('me', 'me@somewhere.net', 'localhost', 'god');
        $creator->save();
        $room = new Room($creator, '1', 'Test', 'test');
        $room->save();
        $room->join($creator);
        return $room;
    }

    public static function member(Room $room, User $user): Member
    {
        $member = new Member($room->uid, $user->uid);
        $member->save();
        return $member;
    }

    public static function account(User $user): Account
    {
        $account = new Account();
        $account->name = 'test';
        $account->user_uid = $user->uid;
        $account->save();
        return $account;
    }

    public static function mail(Account $account, FileStore $store, string $fixtureMail): Mail
    {
        $mail = new Mail();
        $mail->id = '1';
        $mail->file_id = uniqid();
        $mail->action = Mail::ActionImport;
        $mail->account_uid = $account->uid;
        $mail->save();

        $store->write($mail->file_id, file_get_contents(__DIR__ . '/data/' . $fixtureMail));
        return $mail;
    }

    public static function accountData(): AccountData
    {
        return new AccountData(self::imapData());
    }

    public static function imapData(): stdClass
    {
        $fixture = new stdClass();
        $fixture->imap_url = '1';
        $fixture->smtp_host = '2';
        $fixture->smtp_port = '3';
        $fixture->user = '4';
        $fixture->password = '5';
        return $fixture;
    }

    public static function event(): stdClass
    {
        return json_decode(file_get_contents(__DIR__ . '/data/event.json'));
    }

    public static function eventUrl(): stdClass
    {
        $event = json_decode(file_get_contents(__DIR__ . '/data/event.json'));
        $event->content->msgtype = 'm.file';
        $event->content->url = 'http://nowhere/me.png';
        $event->content->body = 'me';
        return $event;
    }

    public static function eventUnknown(): stdClass
    {
        $event = new stdClass();
        $event->content = new stdClass();
        $event->content->msgtype = 'm.xx';
        return $event;
    }
}