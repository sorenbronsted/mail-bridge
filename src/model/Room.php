<?php

namespace bronsted;

use GuzzleHttp\Exception\ClientException;
use stdClass;

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
        $data->invite     = array_map(function(User $item) {
                                return $item->id;
                            }, $invitations);
        $result           = $http->post($url, $data);

        $room = new Room($creator, $result->room_id, $name, $result->room_alias ?? '');
        $room->save();

        $invitations[] = $creator;
        Member::addAll($room, $invitations);
        return $room;
    }

    public function hasMember(User $user): bool
    {
        try {
            Member::getOneBy(['room_uid' => $this->uid, 'user_uid' => $user->uid]);
        }
        catch(NotFoundException $e) {
            return false;
        }
        return true;
    }

    public function invite(Http $http, User $user): void
    {
        $creator = User::getByUid($this->creator_uid);
        Member::addAll($this, [$user]);

        //$this->log->debug('Invite ' . $user->id . ' to room ' . $roomId);
        $url           = '/_matrix/client/r0/rooms/' . urlencode($this->id) . '/invite?user_id=' . $creator->id;
        $data          = new stdClass();
        $data->user_id = $user->id;

        $tryAgain = false;
        do {
            try {
                $tryAgain = false;
                $http->post($url, $data);
            } catch (ClientException $e) {
                if ($e->getCode() == 429) { // Rate limit
                    $resp = json_decode($e->getResponse()->getBody()->getContents());
                    $this->log->debug("Rate limit. retry after ms: " . $resp->retry_after_ms);
                    usleep($resp->retry_after_ms * 1000);
                    $tryAgain = true;
                } else {
                    throw $e;
                }
            }
        } while ($tryAgain);
    }

    public function join(Http $http, User $user)
    {
        $url = '/_matrix/client/r0/rooms/' . urlencode($this->id) . '/join?user_id=' . urlencode($user->id);
        $data  = new stdClass();
        $http->post($url, $data);
    }

    public function send(Http $http, User $from, $text, $html)
    {
        $uid = uniqid('', true); //TODO should derived from text or $html some how
        $url                  = '/_matrix/client/r0/rooms/' . urlencode($this->id) . '/send/m.room.message/' . $uid . '?user_id=' . urlencode($from->id);
        $data                 = new stdClass();
        $data->msgtype        = 'm.text';
        $data->body           = $text;
        $data->format         = 'org.matrix.custom.html';
        $data->formatted_body = $html;
        $data->sender         = $from->id;
        $http->put($url, $data);
    }
}