<?php

namespace bronsted;

use Exception;

class FileStoreTest extends TestCase
{
    public function testCreate()
    {
        $config = $this->container->get(AppServiceConfig::class);
        $config->fileStoreRoot = '/tmp/' . uniqid();
        $store = new FileStore($config);
        $this->assertTrue(file_exists($config->fileStoreRoot));
    }
}