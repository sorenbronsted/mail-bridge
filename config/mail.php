<?php

namespace bronsted;

use DI\Container;

function mail(Container $container)
{
    $container->set(Imap::class, new Imap());
}
