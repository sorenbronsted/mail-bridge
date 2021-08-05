<?php

namespace bronsted;

use DI\Container;

function file(Container $container)
{
    $container->set(File::class, new File());
}
