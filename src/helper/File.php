<?php

namespace bronsted;

class File
{
    private string $root;

    public function root(string $root)
    {
        $this->root = $root;
    }

    public function write(string $filename, string $content)
    {
        if (!file_exists($this->root)) {
            mkdir($this->root, 0664, true);
        }
        file_put_contents($this->root . '/' . $filename, $content);
    }
}
