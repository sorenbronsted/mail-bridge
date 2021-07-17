<?php
namespace bronsted;

use Exception;
use ZBateson\MailMimeParser\Header\AddressHeader;
use ZBateson\MailMimeParser\Header\DateHeader;
use ZBateson\MailMimeParser\Header\HeaderConsts;
use ZBateson\MailMimeParser\Message;

class Imap
{
    private AddressHeader $to;
    private User $from;
    private User $user;
    private Http $http;
    private Message $message;
    private DateHeader $ts;

    public function __construct(Http $http, User $user)
    {
        $this->http = $http;
        $this->user = $user;
    }

    public function fetch()
    {
        throw new Exception('Not implemented yet');
    }

    public function import($fh)
    {
        $this->parse($fh);

        if (count($this->to->getAddresses()) > 1) {
            $room = $this->getOrCreateMultiUserRoom();
        } else {
            $room = $this->getOrCreateDirectRoom();
        }
        $room->send($this->http, $this->from, $this->message, $this->ts->getDateTime());
    }

    private function parse($fh)
    {
        $this->message = Message::from($fh);

        $this->to = $this->message->getHeader(HeaderConsts::TO);

        $this->ts = $this->message->getHeader(HeaderConsts::DATE);

        $this->subject = $this->message->getHeader(HeaderConsts::SUBJECT);
        if (is_object($this->subject)) {
            $this->subject = $this->subject->getValue();
        }

        $idx = strrpos($this->subject, ':');
        if ($idx !== false) {
            $this->subject = trim(substr($this->subject, $idx + 1));
        }
        if (empty($this->subject)) {
            $datetime = $this->ts->getDateTime();
            $this->subject = 'No subject ' . $datetime->format('Y-m-d H:i');
        }

        $from = $this->message->getHeader(HeaderConsts::FROM)->getAddresses()[0];
        $this->from = User::getOrCreate($this->http, $from->getName(), $from->getEmail());
    }

    private function getOrCreateMultiUserRoom(): Room
    {
        $room = null;
        try {
            $room = Room::getOneBy(['name' => $this->subject]);
            if (!$room->hasMember($this->from)) {
                $room->addUser($this->http, $this->from);
            }
            foreach ($this->to->getAddresses() as $address) {
                $member = User::getOrCreate($this->http, $address->getName(), $address->getEmail());
                if (!$room->hasMember($member)) {
                    $room->addUser($this->http, $member);
                }
            }
        } catch (NotFoundException $e) {
            $invitations = [];
            foreach ($this->to->getAddresses() as $address) {
                $member = User::getOrCreate($this->http, $address->getName(), $address->getEmail());
                $invitations[] = $member;
            }
            $room = Room::create($this->http, $this->subject, $this->from, $invitations);
        }
        return $room;
    }

    private function getOrCreateDirectRoom(): Room
    {
        $room = null;
        try {
            $name = $this->from->name;
            if (empty($name)) {
                $name = $this->from->email;
            }
            $room = Room::getOneBy(['name' => $name]);
        } catch (NotFoundException $e) {
            $invitations = [$this->user];
            $room = Room::create($this->http, $name, $this->from, $invitations);
        }
        return $room;
    }
}