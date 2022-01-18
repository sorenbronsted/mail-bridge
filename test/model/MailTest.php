<?php

namespace bronsted;

class MailTest extends TestCase
{
    private FileStore $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->store = $this->container->get(FileStore::class);
    }

    public function testNotValid()
    {
        $mail = new Mail();
        $this->expectExceptionMessageMatches('/empty/');
        $mail->save();
    }

    public function testDestroy()
    {
        $mail = Fixtures::mailFromFile(Fixtures::account(Fixtures::user()), $this->store, 'direct.mime');
        $this->assertTrue(file_exists($mail->getFileInfo($this->store)));
        $message = $mail->getMessage($this->store);
        $this->assertNotNull($message);

        $mail->destroy($this->store);

        $this->assertFalse(file_exists($mail->getFileInfo($this->store)->getPathname()));
        $this->expectExceptionMessageMatches('/failed/');
        $message = $mail->getMessage($this->store);
    }
}