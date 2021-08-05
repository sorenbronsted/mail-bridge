<?php

namespace bronsted;

class MemberTest extends TestCase
{
    public function testAddWhenNotExists()
    {
        $room = Fixtures::room();
        $user = Fixtures::user();
        $this->assertEquals(1, count(Member::getAll()));

        Member::add($room, $user);
        $this->assertEquals(2, count(Member::getAll()));
    }

    public function testAddWhenExists()
    {
        $room = Fixtures::room();
        $user = Fixtures::user();
        Member::add($room, $user);
        $this->assertEquals(2, count(Member::getAll()));

        Member::add($room, $user);
        $this->assertEquals(2, count(Member::getAll()));
    }
}