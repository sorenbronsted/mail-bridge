<?php

namespace bronsted;

class RoomTest extends TestCase
{
    public function testCreate()
    {
        $this->assertEquals(0, count(Room::getAll()));
        $this->assertEquals(0, count(Member::getAll()));

        $user = Fixtures::user();
        Room::create('1', 'Test', $user);

        $this->assertEquals(1, count(Room::getAll()));
        $this->assertEquals(1, count(Member::getAll()));
    }

    public function testHasMember()
    {
        $this->assertEquals(0, count(Member::getAll()));

        $room = Fixtures::room();
        $user = Fixtures::user();
        $this->assertFalse($room->hasMember($user));

        $room->join($user);
        $this->assertTrue($room->hasMember($user));
    }

    public function testGetMailRecipients()
    {
        $room = Fixtures::room();
        $creator = User::getByUid($room->creator_uid);
        $this->assertEquals(0, count($room->getMailRecipients($creator)));

        $user = Fixtures::user();
        $room->join($user);
        $this->assertEquals(1, count($room->getMailRecipients($creator)));
    }
}