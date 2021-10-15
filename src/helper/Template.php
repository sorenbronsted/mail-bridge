<?php

namespace bronsted;

use Exception;
use stdClass;

class Template
{
    protected $mount;
    protected $name;
    protected $data;

    public function __construct(string $mount, string $name, stdClass $data)
    {
        $this->mount = $mount;
        $this->name = $name;
        $this->data = $data;
    }

    public function render(): stdClass
    {
        $file = dirname(__DIR__) . '/view/' . $this->name . '.php';
        if (!file_exists($file)) {
            throw new Exception('View not found: ' . $this->name);
        }
        $result = new stdClass();
        $result->mount = $this->mount;
        ob_start();
        require($file);
        $result->html = ob_get_clean();
        return $result;
    }
}
