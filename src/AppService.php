<?php

namespace bronsted;

use Psr\Http\Message\MessageInterface;
use Psr\Log\LoggerInterface;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use stdClass;
use Throwable;

class AppService
{
    private AppServerConfig $config;
    private LoggerInterface $log;
    private Imap $imap;
    private MatrixClient $client;

    public function __construct(LoggerInterface $log, AppServerConfig $source, Imap $imap, MatrixClient $client)
    {
        $this->log = $log;
        $this->config = $source;
        $this->imap = $imap;
        $this->client = $client;
    }

    public function events(Request $request, Response $response, array $args): MessageInterface
    {
        $data = (object)$request->getParsedBody();
        $args = (object)$args;

        try {
            $this->consumeEvents($args->txnId, $data);
        } catch (Throwable $t) {
            $this->log->error($t->getMessage());
        }

        $response->getBody()->write(json_encode(new stdClass()));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function hasUser(Request $request, Response $response, array $args): MessageInterface
    {
        $args = (object)$args;
        $this->validateCredentials($request);
        try {
            User::getOneBy(['id' => $args->userId]);
            $response->getBody()->write('{}');
            return $response->withHeader('Content-Type', 'application/json');
        } catch (NotFoundException $e) {
            return $response->withStatus(404);
        }
    }

    public function hasRoom(Request $request, Response $response, array $args): MessageInterface
    {
        $args = (object)$args;
        $this->validateCredentials($request);
        try {
            Room::getOneBy(['alias' => $args->roomAlias]);
            $response->getBody()->write('{}');
            return $response->withHeader('Content-Type', 'application/json');
        } catch (NotFoundException $e) {
            return $response->withStatus(404);
        }
    }

    private function validateCredentials(Request $request)
    {
        $args = (object)$request->getQueryParams();
        //401 = missing credentials
        if (!isset($args->access_token)) {
            throw new CredentialException('Missing token', 401);
        }
        //403 = wrong credentials
        if ($args->access_token != $this->config->tokenHomeServer) {
            throw new CredentialException('Wrong token', 403);
        }
    }

    private function consumeEvents(string $txnId, ?stdClass $events = null)
    {
        if ($events == null) {
            return;
        }

        /* capture events */
        $file = __DIR__ . '/data/events/' . $txnId . '.json';
        //file_put_contents($file, json_encode($events));


        foreach ($events->events as $event) {
            if (!isset($event->type)) {
                continue;
            }

            if ($this->isPuppet($event->user_id)) {
                continue;
            }

            switch ($event->type) {
                case 'm.room.create':
                    $this->createRoom($event);
                    break;
                case 'm.room.name':
                    $this->setRoomName($event);
                    break;
                case 'm.room.member':
                    $this->roomMember($event);
                    break;
                case 'm.room.message':
                    $this->message($event);
                    break;
            }
        }
    }

    private function createRoom(stdClass $event)
    {
        try {
            Room::getOneBy(['id' => $event->room_id]);
        } catch (NotFoundException $e) {
            $creator = User::getOneBy($event->user_id);
            Room::create($event->room_id, '', $creator);
        }
    }

    private function setRoomName(stdClass $event)
    {
        if (empty($event->content->name)) {
            return;
        }
        $room = Room::getOneBy(['id' => $event->room_id]);
        $room->name = $event->content->name;
        $room->save();
    }

    private function roomMember(stdClass $event)
    {
        if ($event->content->membership == 'invite' && $this->isPuppet($event->state_key)) {
            $user = null;
            $room = Room::getOneBy(['id' => $event->room_id]);
            try {
                $user = User::getOneBy(['id' => $event->state_key]);
                if (!$room->hasMember($user)) {
                    $room->join($user);
                }
            } catch (NotFoundException $e) {
                $user = new User($event->content->displayname);
                $user->setEmailById($event->state_key);
                $user->save();
                $room->join($user);
            }
        }
    }

    private function message(stdClass $event)
    {
        $sender = User::getOneBy(['id' => $event->sender]);
        if (!$sender->email) {
            Log::warning('Sender has no email, so no delivery');
            return;
        }

        $room = Room::getOneBy(['id' => $event->room_id]);
        $recipients = $room->getMailRecipients($sender);
        // TODO html body and attachments
        if ($event->content->msgtype == 'm.text') {
            $this->imap->send($sender, $recipients, $room->name, $event->content->body);
        }
    }

    private function isPuppet($id): bool
    {
        $prefix = '@mail_';
        return substr($id, 0, strlen($prefix)) == $prefix;
    }
}
