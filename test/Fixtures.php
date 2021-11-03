<?php

namespace bronsted;

use stdClass;

class Fixtures
{
    public static function clean()
    {
        $con = ModelObject::getConnection();
        $tables = ['user', 'room', 'member', 'account'];
        foreach($tables as $table) {
            $con->exec("delete from $table");
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

    public static function accountData(): AccountData
    {
        return new AccountData(self::imapData());
    }

    public static function imapData(): stdClass
    {
        $fixture = new stdClass();
        $fixture->imap_url = '1';
        $fixture->smtp_host = '2';
        $fixture->user = '4';
        $fixture->password = '5';
        return $fixture;
    }
}