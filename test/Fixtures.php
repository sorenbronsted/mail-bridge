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

    public static function puppet(AppServiceConfig $config): User
    {
        $address = new AddressPart(new MbWrapper(), 'Foo Bar', 'foo@bar.com');
        return User::fromMail($address, $config);
    }

    public static function user(): User
    {
        return User::fromId('@foo:bar.com', 'Foo Bar');
    }

    public static function room(AppServiceConfig $config, ?User $user = null): Room
    {
        if (empty($user)) {
            $user = self::puppet($config);
        }
        $room = new Room('#1:' . $config->domain, Room::toAlias($config, 'test-subject'), 'Test', [$user]);
        return $room;
    }

    public static function account(User $user): Account
    {
        $account = new Account();
        $account->name = 'test';
        $account->user_id = $user->getId();
        $account->state = Account::StateOk;
        $account->save();
        return $account;
    }

    public static function mailFromFile(Account $account, FileStore $store, string $fixtureMailName): Mail
    {
        return Mail::createFromMail($account, $store, file_get_contents(__DIR__ . '/data/' . $fixtureMailName));
    }

    public static function mailFromEvent(MatrixClient $client, AppServiceConfig $config, Http $http, FileStore $store, ?stdClass $event = null): Mail
    {
        return Mail::createFromEvent($client, $config, $http, $store, $event ?? self::event('event_text.json'));
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

    public static function event(string $name): stdClass
    {
        return json_decode(file_get_contents(__DIR__ . '/data/' . $name));
    }
}