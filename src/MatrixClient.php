<?php

namespace bronsted;

use DateTime;
use stdClass;
use ZBateson\MailMimeParser\Header\HeaderConsts;
use ZBateson\MailMimeParser\Message;

/**
 * This is some of the Maxtrix Client Server Api
 *
 * @package bronsted
 */
class MatrixClient
{
    private Http $http;

    public function __construct(Http $http)
    {
        $this->http = $http;
    }

    public function createUser(User $user)
    {
        $url = '/_matrix/client/r0/register';
        $data = new stdClass();
        $data->type = "m.login.application_service";
        $data->username = $user->localId();
        $this->http->post($url, $data);

        $url = '/_matrix/client/r0/profile/' . urlencode($user->id) . '/displayname?user_id=' . urlencode($user->id);
        $data = new stdClass();
        $data->displayname = $user->name;
        $this->http->put($url, $data);
    }

    public function createRoom(string $name, User $creator): string
    {
        $url = '/_matrix/client/r0/createRoom?user_id=' . urlencode($creator->id);
        $data = new stdClass();
        $data->visibility = 'private';
        $data->name = $name;
        $result = $this->http->post($url, $data);
        return $result->room_id;
    }

    public function invite(Room $room, User $user)
    {
        $creator = User::getByUid($room->creator_uid);
        $url = '/_matrix/client/r0/rooms/' . urlencode($room->id) . '/invite?user_id=' . $creator->id;
        $data = new stdClass();
        $data->user_id = $user->id;
        $this->http->post($url, $data);
    }

    public function join(Room $room, User $user)
    {
        $url = '/_matrix/client/r0/rooms/' . urlencode($room->id) . '/join?user_id=' . urlencode($user->id);
        $data  = new stdClass();
        $this->http->post($url, $data);
    }

    public function send(Room $room, User $from, Message $message, DateTime $ts)
    {
        //TODO how to store historical messages with $ts
        $uid = md5($message->getHeaderValue(HeaderConsts::MESSAGE_ID));
        $url = '/_matrix/client/r0/rooms/' . urlencode($room->id) . '/send/m.room.message/' . urlencode($uid) . '?user_id=' . urlencode($from->id);

        $data = new stdClass();
        $data->msgtype = 'm.text';
        $data->body = $message->getTextContent() ?? strip_tags($message->getHtmlContent());
        $data->format = 'org.matrix.custom.html';
        $data->formatted_body = $message->getHtmlContent() ?? '';
        $data->sender = $from->id;
        $this->http->put($url, $data);

        for ($i = 0; $i < $message->getAttachmentCount(); $i++) {
            $attachment = $message->getAttachmentPart($i);
            $type = $attachment->getHeaderValue(HeaderConsts::CONTENT_TYPE);
            $name = $attachment->getHeaderParameter(HeaderConsts::CONTENT_TYPE, 'name') ?? uniqid();

            $this->upload($room, $from, $uid."+$i", $attachment->getContentStream(), $name, $type);
        }
    }

    public function upload(Room $room, User $from, string $uid, $stream, string $name, string $type)
    {
        $url = '/_matrix/media/r0/upload?filename=' . urlencode($name);
        $result = $this->http->postStream($url, $type, $stream);

        $url = '/_matrix/client/r0/rooms/' . urlencode($room->id) . '/send/m.room.message/' . urlencode($uid) . '?user_id=' . urlencode($from->id);
        $data = new stdClass();
        $data->msgtype = 'm.file';
        $data->body = $name;
        $data->url = $result->content_uri;
        $this->http->put($url, $data);
    }
}