<?php

namespace bronsted;

use ZBateson\MailMimeParser\Header\Part\AddressPart;
use ZBateson\MbWrapper\MbWrapper;

class UserTest extends TestCase
{
    public function testFromMail()
    {
        $name = 'Foo Bar';
        $email = 'foo@bar.com';
        $address = new AddressPart(new MbWrapper(), $name, $email);
        $user = User::fromMail($address, 'matrix.com');
        $this->assertEquals('@mail_foo/bar.com:matrix.com', $user->getId());
        $this->assertEquals($name, $user->getName());
        $this->assertEquals($email, $user->getEmail());
    }

    public function testFromNotValidMail()
    {
        $name = 'Foo Bar';
        $email = 'foo@bar';
        $address = new AddressPart(new MbWrapper(), $name, $email);
        $this->expectExceptionMessageMatches('/not valid/');
        $user = User::fromMail($address, 'matrix.com');
    }

    public function testPuppetFromId()
    {
        $name = 'Foo Bar';
        $id = '@mail_foo/bar.com:matrix.com';
        $user = User::fromId($id, $name);
        $this->assertEquals($name, $user->getName());
        $this->assertEquals('foo@bar.com', $user->getEmail());
    }

    public function testFromId()
    {
        $name = 'Foo Bar';
        $id = '@foo:bar.com';
        $user = User::fromId($id, $name);
        $this->assertEquals($name, $user->getName());
        $this->assertEmpty($user->getEmail());
    }

    public function testFromNotValidId()
    {
        $name = 'Foo Bar';
        $id = '@mail_foo/bar:matrix.com';
        $this->expectExceptionMessageMatches('/not valid/');
        $user = User::fromId($id, $name);
    }

    public function testEmptyId()
    {
        $this->expectExceptionMessageMatches('/not be empty/');
        new User('', 'Foo');
    }

    public function testEmptyName()
    {
        $this->expectExceptionMessageMatches('/not be empty/');
        new User('foo@bar.com', '');
    }

    public function testEmptyDomain()
    {
        $this->expectExceptionMessageMatches('/not be empty/');
        new User('foo@bar.com', 'Foo');
    }

    public function testNotValidEmail()
    {
        $this->expectExceptionMessageMatches('/not valid/');
        User::validateEmail('x');
    }

    public function testValidEmail()
    {
        User::validateEmail('foo@bar.com');
        $this->assertTrue(true);
    }
}
