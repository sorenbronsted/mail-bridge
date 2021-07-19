<?php

namespace bronsted;

use DateTime;
use stdClass;
use ZBateson\MailMimeParser\Header\HeaderConsts;
use ZBateson\MailMimeParser\Message;

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

    public static function create(Http $http, string $name, User $creator, array $invitations): Room
    {
        // allways invite the bot
        //$invitations[] = '@_mail_bot:' . $this->config->domain;

        $url              = '/_matrix/client/r0/createRoom?user_id=' . urlencode($creator->id);
        $data             = new stdClass();
        $data->visibility = 'private';
        $data->name       = $name;
        $result           = $http->post($url, $data);

        $room = new Room($creator, $result->room_id, $name, $result->room_alias ?? '');
        $room->save();

        Member::addLocal($room, $creator);

        foreach($invitations as $user) {
            Member::add($http, $room, $user);
        }
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

    public function addUser(Http $http, User $user)
    {
        Member::add($http, $this, $user);
    }

    public function invite(Http $http, User $user): void
    {
        $creator = User::getByUid($this->creator_uid);

        //$this->log->debug('Invite ' . $user->id . ' to room ' . $roomId);
        $url           = '/_matrix/client/r0/rooms/' . urlencode($this->id) . '/invite?user_id=' . $creator->id;
        $data          = new stdClass();
        $data->user_id = $user->id;
        $http->post($url, $data);
    }

    public function join(Http $http, User $user)
    {
        $url = '/_matrix/client/r0/rooms/' . urlencode($this->id) . '/join?user_id=' . urlencode($user->id);
        $data  = new stdClass();
        $http->post($url, $data);
    }

    public function send(Http $http, User $from, Message $message, DateTime $ts)
    {
        // Asumes that a user can only one mail pr second
        $uid = md5($message->getHeaderValue(HeaderConsts::MESSAGE_ID));
        //echo $from->email . $message->getHeaderValue(HeaderConsts::MESSAGE_ID) . PHP_EOL;
        //echo $uid . PHP_EOL;

        $url = '/_matrix/client/r0/rooms/' . urlencode($this->id) . '/send/m.room.message/' . urlencode($uid) . '?user_id=' . urlencode($from->id);

        $data = new stdClass();
        $data->msgtype = 'm.text';
        $data->body = $message->getTextContent() ?? strip_tags($message->getHtmlContent());
        $data->format = 'org.matrix.custom.html';
        $data->formatted_body = $message->getHtmlContent() ?? '';
        $data->sender = $from->id;
        //echo $url . PHP_EOL;
        //var_dump($data);
        $http->put($url, $data);

        for ($i = 0; $i < $message->getAttachmentCount(); $i++) {
            $attachment = $message->getAttachmentPart($i);
            $type = $attachment->getHeaderValue(HeaderConsts::CONTENT_TYPE);
            $name = $attachment->getHeaderParameter(HeaderConsts::CONTENT_TYPE, 'name') ?? uniqid();

            $url = '/_matrix/media/r0/upload?filename=' . urlencode($name);
            $result = $http->postStream($url, $type, $attachment->getContentStream());

            $url = '/_matrix/client/r0/rooms/' . urlencode($this->id) . '/send/m.room.message/' . urlencode($uid . "+$i") . '?user_id=' . urlencode($from->id);

            $data = new stdClass();
            $data->msgtype = 'm.file';
            $data->body = $name;
            $data->url = $result->content_uri;
            $http->put($url, $data);
        }
    }

    public function getMailRecipients(User $sender): DbCursor
    {
        $sql = "select u.* from user u join member m on u.uid = m.user_uid ".
            "where m.room_uid = :room_uid and u.email is not null and length(u.email) > 0 and u.uid != :sender_uid";
        return User::getObjects($sql, ['room_uid' => $this->uid, 'sender_uid' => $sender->uid]);
    }
}
