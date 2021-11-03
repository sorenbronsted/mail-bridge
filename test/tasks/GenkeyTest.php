<?php

namespace bronsted;

class GenKeyTest extends TestCase
{
    public function testRun()
    {
        $task = new GenKey();
        ob_start();
        $task->run([]);
        $out = ob_get_clean();
        $this->assertNotEmpty($out);
        $this->assertStringMatchesFormat('%s', $out);
    }
}