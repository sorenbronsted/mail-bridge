<?php

namespace bronsted;

use Exception;
use stdClass;

class ImapAccountTest extends TestCase
{
    public function testValidateEmpty()
    {
        $this->expectException(Exception::class);
        ImapAccount::validate([]);
    }

    public function testValidateWrongData()
    {
        $this->expectException(Exception::class);
        ImapAccount::validate(['wrong' => 'data']);
    }

    public function testValidateSomeWrongData()
    {
        $this->expectException(Exception::class);
        ImapAccount::validate(['imap_url' => '{some url}']);
    }

    public function testValidateWithNoValues()
    {
        $fixture = new stdClass();
        $fixture->imap_url = '';
        $fixture->smtp_host = '';
        $fixture->smtp_port = '';
        $fixture->user = '';
        $fixture->password = '';

        $this->expectException(Exception::class);
        ImapAccount::validate((array)$fixture);
    }

    public function testValidateOk()
    {
        $fixture = new stdClass();
        $fixture->imap_url = '1';
        $fixture->smtp_host = '2';
        $fixture->smtp_port = '3';
        $fixture->user = '4';
        $fixture->password = '5';
        ImapAccount::validate((array)$fixture);
        $this->assertTrue(true);
    }

    public function testParse()
    {
        $fixture = new stdClass();
        $fixture->imap_url = '1';
        $fixture->smtp_host = '2';
        $fixture->smtp_port = '3';
        $fixture->user = '4';
        $fixture->password = '5';

        $account = ImapAccount::parse((array)$fixture);

        foreach($fixture as $name => $value) {
            $this->assertEquals($value, $account->$name);
        }
    }
}