<?php

namespace bronsted;

use Exception;
use Symfony\Component\Finder\Finder;

class FileStoreTest extends TestCase
{
    public function testCreate()
    {
        $root = '/tmp/' . uniqid();
        $store = new FileStore($root);
        $this->assertEquals(3, count(glob($root . '/*')));
        $this->assertEquals(2, count(glob($root . '/fail/*')));
    }

    public function testGetDirFail()
    {
        $store = $this->container->get(FileStore::class);
        $this->expectException(Exception::class);
        $store->getDir(10);
    }
}