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

    public static function addLocal(Room $room, User $user)
    {
        try {
            Member::getOneBy(['room_uid' => $room->uid, 'user_uid' => $user->uid]);
        } catch (NotFoundException $e) {
            $member = new Member($room->uid, $user->uid);
            $member->save();
        }
    }

    public static function add(Http $http, Room $room, User $user)
    {
        try {
            Member::getOneBy(['room_uid' => $room->uid, 'user_uid' => $user->uid]);
        } catch (NotFoundException $e) {
            $room->invite($http, $user);
            $room->join($http, $user);
            $member = new Member($room->uid, $user->uid);
            $member->save();
        }
    }
}
