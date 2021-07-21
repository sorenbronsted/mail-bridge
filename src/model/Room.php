<?php

namespace bronsted;

class Room extends ModelObject
{
    protected ?string $id;
    protected ?string $name;
    protected ?string $alias;
    protected ?int $creator_uid;

    public function __construct(?User $creator = null, ?string $id = null, ?string $name = null, ?string $alias = null)
    {
        $this->creator_uid = $creator->uid ?? null;
        $this->id = $id;
        $this->name = $name;
        $this->alias = $alias;
    }

    public static function create(string $id, string $name, User $creator): Room
    {
        $room = new Room($creator, $id, $name);
        $room->save();
        Member::add($room, $creator);
        return $room;
    }

    public function hasMember(User $user): bool
    {
        try {
            Member::getOneBy(['room_uid' => $this->uid, 'user_uid' => $user->uid]);
        } catch (NotFoundException $e) {
            return false;
        }
        return true;
    }

    public function join(User $user)
    {
        Member::add($this, $user);
    }

    public function getMailRecipients(User $sender): DbCursor
    {
        // Get members for this room which has valid email and id starts with '@mail_' and it not the sender
        $sql = "select u.* from user u join member m on u.uid = m.user_uid ".
            "where m.room_uid = :room_uid and u.email is not null and length(u.email) > 0 ".
            "and u.uid != :sender_uid and substr(u.id,1,6) = '@mail_'";
        return User::getObjects($sql, ['room_uid' => $this->uid, 'sender_uid' => $sender->uid]);
    }
}
