<?php

namespace bronsted;

use Exception;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use stdClass;

class MatrixSdk
{
    private Client $http;
    private AppServerConfig $config;
    private LoggerInterface $log;
    private array $header;
    private User $user;

    public function __construct(AppServerConfig $config, Client $http, LoggerInterface $log, User $user)
    {
        $this->config = $config;
        $this->http = $http;
        $this->log = $log;
        $this->user = $user;
        $this->header = ['Authorization' => 'Bearer ' . $config->tokenAppServer];
    }

    public function sync()
    {
        $url = $this->config->baseUrl . '/_matrix/client/r0/sync?user_id=' . urlencode($this->user->id);
        $result = $this->get($url);
        //file_put_contents(__DIR__ . '/sync/sync.json', json_encode($result));
        foreach ($result->rooms->invite as $roomId => $invite) {
            foreach ($invite->invite_state->events as $event) {
                if ($event->type == 'm.room.name') {
                    $this->rooms[strtolower($event->content->name)] = $roomId;
                }
            }
        }
    }

    public function getOrCreateUser(string $name, string $email): stdClass
    {
        try {
            //$this->log->debug('Get user: ' . $email);
            return $this->getUser($email);
        } catch (NotFoundException $e) {
            if ($e->getCode() == 404) {
                //$this->log->debug('Create user: ' . $email);
                return $this->createUser($name, $email);
            }
            throw $e;
        }
    }

    public function hasMember(string $roomId, stdClass $user): bool
    {
        $url = $this->config->baseUrl . '/_matrix/client/r0/rooms/' . urlencode($roomId) . '/members';
        $members = $this->get($url);
        $result = array_filter($members->chunk, function ($item) use ($user) {
            return $item->type == 'm.room.member' && $item->state_key == $user->id;
        });
        return count($result) > 0;
    }

    public function invite(Room $room, User $user): void
    {
        //$this->log->debug('Invite ' . $user->id . ' to room ' . $roomId);
        $url = $this->config->baseUrl . '/_matrix/client/r0/rooms/' . urlencode($roomId) . '/invite?user_id=';
        /*
        if ($creator != null) {
            $url .= '?user_id=' . urlencode($creator->id);
        }
        */
        $data          = new stdClass();
        $data->user_id = $user->id;

        $tryAgain = false;
        do {
            try {
                $tryAgain = false;
                $this->post($url, $data);
                sleep(1);
            } catch (Exception $e) {
                if ($e->getCode() == 429) {
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

    public function join(string $roomId, string $userId)
    {
        //$this->log->debug('Join ' . $userId . ' to room ' . $roomId);
        $url = $this->config->baseUrl . '/_matrix/client/r0/rooms/' . urlencode($roomId) . '/join?user_id=' . urlencode(($userId));
        $data  = new stdClass();
        $this->post($url, $data);
    }

    public function send(string $roomId, stdClass $user, ?string $text = '', ?string $html = ''): void
    {
        //$this->log->debug('Send from ' . $user->id . ' to room ' . $roomId);
        $uid = uniqid('', true);
        $url = $this->config->baseUrl . '/_matrix/client/r0/rooms/' . urlencode($roomId) . '/send/m.room.message/' . $uid . '?user_id=' . urlencode($user->id);
        $data                 = new stdClass();
        $data->msgtype        = 'm.text';
        $data->body           = $text;
        $data->format         = 'org.matrix.custom.html';
        $data->formatted_body = $html;
        $data->sender         = $user->id;
        $this->put($url, $data);
    }


    private function email2Id(string $email): string
    {
        return '@' . $this->email2LocalId($email) . ':' . $this->config->domain;
    }
}
