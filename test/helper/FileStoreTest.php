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
        $this->assertEquals(3, count(glob($config->fileStoreRoot . '/*')));
        $this->assertEquals(2, count(glob($config->fileStoreRoot . '/fail/*')));
    }

    public function testGetDirFail()
    {
        $store = $this->container->get(FileStore::class);
        $this->expectException(Exception::class);
        $store->getDir(10);
    }
}