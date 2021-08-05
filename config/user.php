<?php

namespace bronsted;

use DI\Container;

function user(Container $container)
{
    $user = null;
    $account = null;
    try {
        $user = User::getOneBy(['email' => 'soren@bronsted.dk']);
        $account = Account::getOneBy(['user_uid' => $user->uid]);
    } catch (NotFoundException $e) {
        $user = new User('Søren Brønsted', 'soren@bronsted.dk', 'syntest.lan', 'sb');
        $user->save();
        $account = new Account();
        $account->user_uid = $user->uid;
        $account->save();
    }
    $container->set(User::class, $user);
    $container->set(Account::class, $account);
}
