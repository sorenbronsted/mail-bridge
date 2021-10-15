<?php

namespace bronsted;

use DateTime;
use HTMLPurifier;
use HTMLPurifier_Config;
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
    private AppServiceConfig $config;

    public function __construct(Http $http, AppServiceConfig $config)
    {
        $this->http = $http;
        $this->config = $config;
    }

    public function createUser(User $user, DateTime $ts)
    {
        $url = '/_matrix/client/r0/register?ts=' . ($ts->format('U') * 1000);
        $data = new stdClass();
        $data->type = "m.login.application_service";
        $data->username = $user->localId();
        $this->http->post($url, $data);

        $url = '/_matrix/client/r0/profile/' . urlencode($user->id) . '/displayname?user_id=' . urlencode($user->id) . '&ts=' . ($ts->format('U') * 1000);
        $data = new stdClass();
        $data->displayname = $user->name;
        $this->http->put($url, $data);
    }

    public function createRoom(string $name, User $creator, DateTime $ts): string
    {
        $url = '/_matrix/client/r0/createRoom?user_id=' . urlencode($creator->id) . '&ts=' . ($ts->format('U') * 1000);
        $data = new stdClass();
        $data->visibility = 'private';
        $data->preset = 'trusted_private_chat'; // This does work as expected se join()
        $data->name = $name;
        $result = $this->http->post($url, $data);
        return $result->room_id;
    }

    public function invite(Room $room, User $user, DateTime $ts)
    {
        $creator = User::getByUid($room->creator_uid);
        $url = '/_matrix/client/r0/rooms/' . urlencode($room->id) . '/invite?user_id=' . $creator->id . '&ts=' . ($ts->format('U') * 1000);
        $data = new stdClass();
        $data->user_id = $user->id;
        $this->http->post($url, $data);
    }

    public function join(Room $room, User $user, DateTime $ts)
    {
        $url = '/_matrix/client/r0/rooms/' . urlencode($room->id) . '/join?user_id=' . urlencode($user->id) . '&ts=' . ($ts->format('U') * 1000);
        $data  = new stdClass();
        $this->http->post($url, $data);

        /* Ensure that matrix users (non-puppets) have full control over the room.
        This is a work around for trusted_private_chat preset not working as expected
        Setting the state event works, but is not enforced, when trying to change the room
        if (!User::isPuppet($user->id)) {
            $creator = User::getByUid($room->creator_uid);

            $data  = new stdClass();
            $data->content = new stdClass();
            $data->content->users = [$user->id => 100];
            $data->event_id = uniqid() . ':' . $this->config->domain;
            $data->origin_server_ts = ($ts->format('U') * 1000);
            $data->room_id = $room->id;
            $data->sender = $creator->id;
            $data->type = 'm.room.power_levels';

            $url = '/_matrix/client/r0/rooms/' . urlencode($room->id) . '/state/' . $data->type . '?user_id=' . urlencode($creator->id) . '&ts=' . ($ts->format('U') * 1000);
            $this->http->put($url, $data);
        }
        */
    }

    public function send(Room $room, User $from, Message $message, DateTime $ts)
    {
        $uid = md5($message->getHeaderValue(HeaderConsts::MESSAGE_ID));
        $url = '/_matrix/client/r0/rooms/' . urlencode($room->id) . '/send/m.room.message/' . urlencode($uid) . '?user_id=' . urlencode($from->id) . '&ts=' . ($ts->format('U') * 1000);

        $data = new stdClass();
        $data->msgtype = 'm.text';
        $data->body = $message->getTextContent() ?? strip_tags($message->getHtmlContent());
        $data->format = 'org.matrix.custom.html';
        $data->formatted_body = $this->sanitize($message->getHtmlContent()) ?? '';
        $data->sender = $from->id;
        $this->http->put($url, $data);

        for ($i = 0; $i < $message->getAttachmentCount(); $i++) {
            $attachment = $message->getAttachmentPart($i);
            $type = $attachment->getHeaderValue(HeaderConsts::CONTENT_TYPE);
            $name = $attachment->getHeaderParameter(HeaderConsts::CONTENT_TYPE, 'name') ?? uniqid();

            $this->upload($room, $from, $uid."+$i", $attachment->getContentStream(), $name, $type, $ts);
        }
    }

    public function upload(Room $room, User $from, string $uid, $stream, string $name, string $type, DateTime $ts)
    {
        $url = '/_matrix/media/r0/upload?filename=' . urlencode($name);
        $result = $this->http->postStream($url, $type, $stream);

        $url = '/_matrix/client/r0/rooms/' . urlencode($room->id) . '/send/m.room.message/' . urlencode($uid) . '?user_id=' . urlencode($from->id) . '&ts=' . ($ts->format('U') * 1000);
        $data = new stdClass();
        $data->msgtype = 'm.file';
        $data->body = $name;
        $data->url = $result->content_uri;
        $this->http->put($url, $data);
    }

    private function sanitize(?string $html = ''): string
    {
        if (empty(trim($html))) {
            return '';
        }
        // taken from https://matrix.org/docs/spec/client_server/r0.6.1#m-room-message-msgtypes
        $allowedTags = "font,del,h1,h2,h3,h4,h5,h6,blockquote,p,".
            "a[name|target|href],ul,ol[start],sup,sub,li,b,i,u,strong,em,strike,".
            "code[class],hr,br,div,table,thead,tbody,tr,th,td,caption,pre,span,img[width|height|alt|title|src]";

        //TODO P2 ensure that javascript on events is stripped off
        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', $allowedTags);
        $cleaner = new HTMLPurifier($config);
        return $cleaner->purify($html);
    }
}