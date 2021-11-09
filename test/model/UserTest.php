<?php

namespace bronsted;

use Exception;
use stdClass;

class UserTest extends TestCase
{
    public function testSetEmailByNoneValidId()
    {
        $user = Fixtures::user();
        $this->expectException(Exception::class);
        $user->setEmailById('@me:localhost');
    }

    public function testSetEmailByEmptyId()
    {
        $user = Fixtures::user();
        $this->expectException(Exception::class);
        $user->setEmailById('');
    }

    public function testSetEmailByValidId()
    {
        $fixture = '@mail_me/somewhere.net:localhost';
        $user = Fixtures::user();
        $this->assertNotEquals($fixture, $user->id);
        $this->assertNotEquals('me@somewhere.net', $user->email);

        $user->setEmailById($fixture);
        $this->assertEquals('me@somewhere.net', $user->email);
        $this->assertEquals($fixture, $user->id);
    }

    public function testCreate()
    {
        $this->assertEquals(0, count(User::getAll()));

        $fixture = new stdClass();
        $fixture->name = 'Yrsa Humbuk';
        $fixture->email = 'yrsa@humbuk.net';
        $fixture->domain = 'syn.lan';
        $fixture->id = User::PUPPET_PREFIX . 'yrsa/humbuk.net:' . $fixture->domain;

        $user = User::create($fixture->name, $fixture->email, $fixture->domain);
        $this->assertEquals(1, count(User::getAll()));

        $this->assertEquals($fixture->name, $user->name);
        $this->assertEquals($fixture->email, $user->email);
        $this->assertEquals($fixture->id, $user->id );
    }

    public function testNonPuppets()
    {
        $this->assertEquals(0, count(User::getNonePuppets()));
        Fixtures::room();
        $this->assertEquals(1, count(User::getNonePuppets()));
    }

    public function testGetOrCreate()
    {
        $id = '@me:somewhere.net';
        $user = User::getOrCreate($id);
        $this->assertEquals($id, $user->id);
        $this->assertEquals($id, $user->name);
    }

    public function testUserName()
    {
        $user = new User();
        $this->expectExceptionMessageMatches('/not be empty/');
        $user->save();
    }
}
