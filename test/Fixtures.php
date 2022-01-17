<?php

namespace bronsted;

use stdClass;
use ZBateson\MailMimeParser\Header\Part\AddressPart;
use ZBateson\MbWrapper\MbWrapper;

class Fixtures
{
    public static function clean()
    {
        $con = Db::getConnection();
        $tables = ['account', 'mail'];
        foreach($tables as $table) {
            $con->execute("delete from $table");
        }
    }

    public static function puppet(string $domain): User
    {
        $address = new AddressPart(new MbWrapper(), 'Foo Bar', 'foo@bar.com');
        return User::fromMail($address, $domain);
    }

    public static function user(): User
    {
        return User::fromId('@foo:bar.com', 'Foo Bar');
    }

    public static function room(MatrixClient $mock, string $domain, ?User $user = null): Room
    {
        if (empty($user)) {
            $user = self::puppet($domain);
        }
        $room = new Room($mock, '#1:' . $domain, 'test-alias', 'Test', [$user]);
        return $room;
    }

    public static function account(User $user): Account
    {
        $account = new Account();
        $account->name = 'test';
        $account->user_id = $user->getId();
        $account->save();
        return $account;
    }

    public static function mail(Account $account, FileStore $store, string $fixtureMailName): Mail
    {
        $mail = new Mail();
        $mail->id = '1';
        $mail->file_id = uniqid();
        $mail->action = Mail::ActionImport;
        $mail->account_uid = $account->uid;
        $mail->save();

        $store->write($mail->file_id, file_get_contents(__DIR__ . '/data/' . $fixtureMailName));
        return $mail;
    }

    public static function accountData(): AccountData
    {
        return new AccountData(self::imapData());
    }

    public static function imapData(): stdClass
    {
        $fixture = new stdClass();
        $fixture->imap_url = 'imap.nowhere';
        $fixture->smtp_host = 'smtp.nowhere';
        $fixture->smtp_port = '465';
        $fixture->email = 'foo@bar.com';
        $fixture->user_name = 'Foo Bar';
        $fixture->password = '1234';
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