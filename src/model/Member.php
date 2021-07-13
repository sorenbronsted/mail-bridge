<?php

namespace bronsted;

class Member extends ModelObject
{
    protected ?int $room_uid;
    protected ?int $user_uid;

    public function __construct(?int $room_uid = null, ?int $user_uid = null)
    {
        $this->room_uid = $room_uid;
        $this->user_uid = $user_uid;
    }

    public static function addAll(Room $room, array $members)
    {
        foreach ($members as $user) {
            try {
                Member::getOneBy(['room_uid' => $room->uid, 'user_uid' => $user->uid]);
            } catch (NotFoundException $e) {
                $member = new Member($room->uid, $user->uid);
                $member->save();
            }
        }
    }
}
