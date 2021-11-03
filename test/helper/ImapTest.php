<?php

namespace bronsted;

require 'vendor/antecedent/patchwork/Patchwork.php';

use Exception;
use PHPUnit\Framework\TestCase;
use stdClass;

use function Patchwork\always;
use function Patchwork\redefine;

class ImapTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        //$this->markTestSkipped('testing');
    }

    public function testSortOk()
    {
        redefine('imap_sort', always(true));

        $imap = new Imap();
        $imap->sort(1, false);
        $this->assertTrue(true);
    }

    public function testSortFail()
    {
        redefine('imap_sort', always(false));

        $imap = new Imap();
        $this->expectException(Exception::class);
        $imap->sort(1, false);
    }

    public function testCountOk()
    {
        redefine('imap_num_msg', always(10));

        $imap = new Imap();
        $result = $imap->count(1, false);
        $this->assertEquals(10, $result);
    }

    public function testCountFail()
    {
        redefine('imap_num_msg', always(false));

        $imap = new Imap();
        $this->expectException(Exception::class);
        $imap->count(1, false);
    }

    public function testHeaderOk()
    {
        redefine('imap_fetch_overview', always(new stdClass()));

        $imap = new Imap();
        $result = $imap->header(1);
        $this->assertTrue(is_object($result));
    }

    public function testHeaderFail()
    {
        redefine('imap_fetch_overview', always(false));

        $imap = new Imap();
        $this->expectException(Exception::class);
        $imap->header(1);
    }

    public function testMessageOk()
    {
        redefine('imap_fetchheader', always('foo'));
        redefine('imap_body', always('bar'));

        $imap = new Imap();
        $result = $imap->message(1);
        $this->assertEquals('foobar', $result);
    }

    public function testMessageHeaderFail()
    {
        redefine('imap_fetchheader', always(false));

        $imap = new Imap();
        $this->expectException(Exception::class);
        $result = $imap->message(1);
    }

    public function testMessageBodyFail()
    {
        redefine('imap_fetchheader', always('foo'));
        redefine('imap_body', always(false));

        $imap = new Imap();
        $this->expectException(Exception::class);
        $imap->message(1);
    }

    public function testOpenOk()
    {
        redefine('imap_open', always(true));
        redefine('imap_close', always(true));

        $accountData = Fixtures::accountData();
        $imap = new Imap();
        $imap->open($accountData);
        $this->assertTrue(true);
    }

    public function testOpenFail()
    {
        redefine('imap_open', always(false));
        redefine('imap_close', always(true));

        $accountData = Fixtures::accountData();
        $imap = new Imap();
        $this->expectException(Exception::class);
        $imap->open($accountData);
    }

    public function testCanConnect()
    {
        redefine('imap_open', always(true));
        redefine('imap_close', always(true));

        $accountData = Fixtures::accountData();
        $imap = new Imap();
        $imap->canConnect($accountData);
        $this->assertTrue(true);
    }
}
