<?php

namespace bronsted;

class CryptoTest extends TestCase
{
    public function testEncrypt()
    {
        $key = Crypto::generateSecretKey();
        $fixture = 'test';
        $result = Crypto::encrypt($fixture, $key);
        $this->assertNotEquals($fixture, $result);
    }

    public function testDecrypt()
    {
        $key = Crypto::generateSecretKey();
        $fixture = 'test';
        $result = Crypto::encrypt($fixture, $key);
        $result = Crypto::decrypt($result, $key);
        $this->assertEquals($fixture, $result);
    }
}