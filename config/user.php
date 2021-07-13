<?php

namespace bronsted;

use DI\Container;

function user(Container $container)
{
    $user = null;
    try {
        $user = User::getOneBy(['email' => 'soren@bronsted.dk']);
    } catch (NotFoundException $e) {
        $user = new User('Søren Brønsted', 'soren@bronsted.dk', 'syntest.lan', 'sb');
        $user->save();
    }
    $container->set(User::class, $user);
}
