<?php

namespace bronsted;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;
use Throwable;

class AppServiceCtrl
{
    private FileStore $store;

    public function __construct(FileStore $store)
    {
        $this->store = $store;
    }

    public function events(ServerRequestInterface $request, ResponseInterface $response, string $txnId): MessageInterface
    {
        $data = (object)$request->getParsedBody();
        try {
            $this->consumeEvents($txnId, $data);
        } catch (Throwable $t) {
            Log::error($t->getMessage());
        }

        $response->getBody()->write(json_encode(new stdClass()));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function hasUser(ResponseInterface $response, string $userId): MessageInterface
    {
        try {
            User::getOneBy(['id' => $userId]);
            $response->getBody()->write('{}');
            return $response->withHeader('Content-Type', 'application/json');
        } catch (NotFoundException $e) {
            return $response->withStatus(404);
        }
    }

    public function hasRoom(ResponseInterface $response, string $roomAlias): MessageInterface
    {
        try {
            Room::getOneBy(['alias' => $roomAlias]);
            $response->getBody()->write('{}');
            return $response->withHeader('Content-Type', 'application/json');
        } catch (NotFoundException $e) {
            return $response->withStatus(404);
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

            if (User::isPuppet($event->user_id)) {
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
            $creator = User::getOrCreate($event->user_id);
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
        if ($event->content->membership == 'invite' && User::isPuppet($event->state_key)) {
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
        $this->sendMessage($sender, $recipients, $room->name, $event);
    }

    public function sendMessage(User $sender, DbCursor $recipients, string $subject, stdClass $event)
    {
        $data = new stdClass();
        $data->sender = $sender;
        $data->recipients = [];
        $data->subject = $subject;
        $data->event = $event;

        // Extract model objects so it can be serialized
        foreach ($recipients as $recipient) {
            $data->recipients[] = $recipient;
        }

        $filename = uniqid() . '.ser';
        $this->store->write($filename, serialize($data));

        $account = Account::getOneBy(['user_uid' => $sender->uid]);
        $mail = new Mail($event->event_id, $filename, Mail::ActionSend, $account->uid);
        $mail->save();
    }
}
