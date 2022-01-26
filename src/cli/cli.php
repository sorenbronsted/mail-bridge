<?php

namespace bronsted;

use Exception;

require 'vendor/autoload.php';

//TODO P2 use something like symfony/console
function run(array $args)
{
    if (empty($args)) {
        throw new Exception('Wrong number of arguments');
    }
    $app = bootstrap();
    $taskName = __NAMESPACE__ . '\\' .array_shift($args);
    $task = $app->getContainer()->get($taskName);
    $task->run($args);
}

array_shift($argv);
run($argv);