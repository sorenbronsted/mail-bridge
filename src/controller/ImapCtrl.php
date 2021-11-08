<?php

namespace bronsted;

use DateTime;
use Exception;
use SplFileInfo;
use SplFileObject;
use stdClass;
use ZBateson\MailMimeParser\Header\AddressHeader;
use ZBateson\MailMimeParser\Header\DateHeader;
use ZBateson\MailMimeParser\Header\HeaderConsts;
use ZBateson\MailMimeParser\Header\Part\AddressPart;
use ZBateson\MailMimeParser\Message;

class ImapCtrl
{
    private AddressHeader $to;
    private User $from;
    private MatrixClient $client;
    private Message $message;
    private DateHeader $ts;
    private AppServiceConfig $config;
    private Imap $imap;
    private FileStore $fileStore;
    private Smtp $smtp;

    public function __construct(AppServiceConfig $config, MatrixClient $client, Imap $imap, FileStore $fileStore, Smtp $smtp)
    {
        $this->config = $config;
        $this->client = $client;
        $this->imap   = $imap;
        $this->fileStore   = $fileStore;
        $this->smtp   = $smtp;
    }

    public function fetch(Account $account)
    {
        $accountData = $account->getAccountData($this->config);
        $this->imap->open($accountData);

        // sort mailbox by date with newest first
        $this->imap->sort(SORTDATE, true);

        $max = $this->imap->count();
        for ($i = 1; $i <= $max; $i++) {
            $header = $this->imap->header($i);
            if ($header->udate < $account->updated->format('U')) {
                break;
            }
            $filename = $account->uid . '-' . uniqid() . '.mime';
            $message = $this->imap->message($i);
            $this->fileStore->write(FileStore::Inbox, $filename, $message);
        }
        $this->imap->close();
        $account->updated = new DateTime();
        $account->save();
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
        $this->fileStore->write(FileStore::Outbox, $filename, serialize($data));
    }

    public function send(SplFileInfo $fileInfo)
    {
        $file = $fileInfo->openFile('r');
        $data = unserialize($file->fread($file->getSize()));

        $account = Account::getOneBy(['user_uid' => $data->sender->uid]);
        $data->accountData = $account->getAccountData($this->config);

        $this->smtp->sendByAccount($data);
    }

    public function import(SplFileInfo $fileInfo)
    {
        $parts = explode('-', $fileInfo->getFilename());
        if (count($parts) != 2) {
            throw new Exception('Wrong filename form: ' . $fileInfo->getFilename());
        }
        $account = Account::getByUid($parts[0]);
        $this->parse($fileInfo);

        if (count($this->to->getAddresses()) > 1) {
            $room = $this->getOrCreateMultiUserRoom($account);
        } else {
            $room = $this->getOrCreateDirectUserRoom($account);
        }

        $this->client->send($room, $this->from, $this->message, $this->ts->getDateTime());
    }

    private function parse(SplFileInfo $fileInfo)
    {
        $fh = $fileInfo->openFile('r');
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
        $this->from = $this->getOrCreateUser($from);
    }

    private function getOrCreateUser(AddressPart $user): User
    {
        $result = null;
        try {
            $result = User::getOneBy(['email' => $user->getEmail()]);
        } catch (NotFoundException $e) {
            $result = User::create($user->getName(), $user->getEmail(), $this->config->domain);
            $this->client->createUser($result, $this->ts->getDateTime());
        }
        return $result;
    }

    private function addUser(Room $room, User $user)
    {
        $this->client->invite($room, $user, $this->ts->getDateTime());
        $this->client->join($room, $user, $this->ts->getDateTime());
        $member = new Member($room->uid, $user->uid);
        $member->save();
    }

    private function getOrCreateMultiUserRoom(Account $account): Room
    {
        $room = null;
        try {
            $room = Room::getOneBy(['name' => $this->subject]);
            if (!$room->hasMember($this->from)) {
                $this->addUser($room, $this->from);
            }
            foreach ($this->to->getAddresses() as $address) {
                $member = $this->getOrCreateUser($address);
                if (!$room->hasMember($member)) {
                    $this->addUser($room, $member);
                }
            }
        } catch (NotFoundException $e) {
            $id = $this->client->createRoom($this->subject, $this->from, $this->ts->getDateTime());
            $room = Room::create($id, $this->subject, $this->from, $account);
            foreach ($this->to->getAddresses() as $address) {
                $member = $this->getOrCreateUser($address);
                $this->addUser($room, $member);
            }
        }

        // Ensure that current account is allways a member
        $user = User::getByUid($account->user_uid);
        if (!$room->hasMember($user)) {
            $room->join($user);
        }
        return $room;
    }

    private function getOrCreateDirectUserRoom(Account $account): Room
    {
        $room = null;
        $name = null;
        try {
            $name = $this->from->name;
            if (empty($name)) {
                $name = $this->from->email;
            }
            $room = Room::getOneBy(['name' => $name]);
        } catch (NotFoundException $e) {
            $id = $this->client->createRoom($name, $this->from, $this->ts->getDateTime());
            $room = Room::create($id, $name, $this->from, $account);
            $user = User::getByUid($account->user_uid);
            $this->addUser($room, $user);
        }
        return $room;
    }
}
