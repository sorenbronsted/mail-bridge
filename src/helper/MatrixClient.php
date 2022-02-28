<?php

namespace bronsted;

use DateTime;
use Exception;
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
    private string $base = '/_matrix/client/v3';

    public function __construct(Http $http, AppServiceConfig $config)
    {
        $this->http = $http;
        $this->config = $config;
    }

    public function hasUser(User $user): bool
    {
        try {
            $url = $this->base . '/profile/' . urlencode($user->getId());
            $this->http->get($url);
            return true;
        } catch (Exception $e) {
            if ($e->getCode() == 404) {
                return false;
            }
            else {
                throw $e;
            }
        }
    }

    public function createUser(User $user)
    {
        $url = $this->base . '/register';
        $data = new stdClass();
        $data->type = "m.login.application_service";
        $data->username = $user->localId();
        $this->http->post($url, $data);

        $url = $this->base . '/profile/' . urlencode($user->getId()) . '/displayname?user_id=' . urlencode($user->getId());
        $data = new stdClass();
        $data->displayname = $user->getName();
        $this->http->put($url, $data);
    }

    public function createRoom(string $name, string $alias, User $creator, bool $direct = false): string
    {
        $url = $this->base . '/createRoom?user_id=' . urlencode($creator->getId());
        $data = new stdClass();
        $data->visibility = 'private';
        $data->preset = 'trusted_private_chat'; // This does work as expected se join()
        $data->name = $name;
        $data->room_alias_name = $alias;
        $data->is_direct = $direct;
        $result = $this->http->post($url, $data);
        return $result->room_id;
    }

    public function getRoomIdByAlias(string $alias): string
    {
        $url = $this->base . '/directory/room/' . urlencode($alias);
        $result = $this->http->get($url);
        return $result->room_id;
    }

    public function getRoomName(string $id): string
    {
        $event = 'm.room.name';
        $url = $this->base . '/rooms/' . urlencode($id) . '/state/' . urlencode($event);
        $result = $this->http->get($url);
        return $result->content->name;
    }

    public function getRoomAlias(string $id): string
    {
        $event = 'm.room.canonical_alias';
        $url = $this->base . '/rooms/' . urlencode($id) . '/state/ ' . urlencode($event);
        $result = $this->http->get($url);
        return $result->content->alias;
    }

    public function setRoomAlias(string $id, string $alias): void
    {
        $url = $this->base . '/directory/rooms/' . urlencode($alias);
        $this->http->put($url, (object)['room_id' => $id]);
    }

    public function getRoomMembers(string $id): array
    {
        $url = $this->base . '/room/' . urlencode($id) . 'joined_members';
        $result = $this->http->get($url);
        $members = [];
        foreach ($result->joined as $id => $body) {
            $members[] = User::fromId($id, $body->display_name);
        }
        return $members;
    }

    public function invite(Room $room, User $user, Account $account): void
    {
        $url = $this->base . '/rooms/' . urlencode($room->getId()) . '/invite?user_id=' . urlencode($account->user_id);
        $data = new stdClass();
        $data->user_id = $user->getId();
        $this->http->post($url, $data);
    }

    public function join(Room $room, User $user)
    {
        $url = $this->base . '/rooms/' . urlencode($room->getId()) . '/join?user_id=' . urlencode($user->getId());
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
        $url = $this->base . '/rooms/' . urlencode($room->getId()) . '/send/m.room.message/' . urlencode($uid) . '?user_id=' . urlencode($from->getId()) . '&ts=' . ($ts->format('U') * 1000);

        $data = new stdClass();
        $data->msgtype = 'm.text';
        $data->body = $message->getTextContent() ?? strip_tags($message->getHtmlContent());
        $data->format = 'org.matrix.custom.html';
        $data->formatted_body = $this->sanitize($message->getHtmlContent()) ?? '';
        $data->sender = $from->getId();
        $this->http->put($url, $data);

        for ($i = 0; $i < $message->getAttachmentCount(); $i++) {
            $attachment = $message->getAttachmentPart($i);
            $type = $attachment->getHeaderValue(HeaderConsts::CONTENT_TYPE);
            $name = $attachment->getHeaderParameter(HeaderConsts::CONTENT_TYPE, 'name') ?? uniqid();

            $this->upload($room, $from, $uid . "+$i", $attachment->getContentStream(), $name, $type, $ts);
        }
    }

    public function upload(Room $room, User $from, string $uid, $stream, string $name, string $type, DateTime $ts)
    {
        $url = $this->base . '/upload?filename=' . urlencode($name);
        $result = $this->http->postStream($url, $type, $stream);

        $url = '/_matrix/client/v3/rooms/' . urlencode($room->getId()) . '/send/m.room.message/' . urlencode($uid) . '?user_id=' . urlencode($from->getId()) . '&ts=' . ($ts->format('U') * 1000);
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
        $allowedTags = "font,del,h1,h2,h3,h4,h5,h6,blockquote,p," .
            "a[name|target|href],ul,ol[start],sup,sub,li,b,i,u,strong,em,strike," .
            "code[class],hr,br,div,table,thead,tbody,tr,th,td,caption,pre,span,img[width|height|alt|title|src]";

        //TODO P2 ensure that javascript on events is stripped off
        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', $allowedTags);
        $cleaner = new HTMLPurifier($config);
        return $cleaner->purify($html);
    }
}
