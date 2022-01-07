<?php

namespace bronsted;

class Member extends DbObject
{
    protected ?int $room_uid;
    protected ?int $user_uid;

    public function __construct(?int $room_uid = null, ?int $user_uid = null)
    {
        parent::__construct();
        $this->room_uid = $room_uid;
        $this->user_uid = $user_uid;
    }

    public static function add(Room $room, User $user)
    {
        try {
            Member::getOneBy(['room_uid' => $room->uid, 'user_uid' => $user->uid]);
        } catch (NotFoundException $e) {
            $member = new Member($room->uid, $user->uid);
            $member->save();
        }
    }
}
