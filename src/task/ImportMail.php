<?php

namespace bronsted;

use Exception;
use SplFileInfo;
use Throwable;
use ZBateson\MailMimeParser\Header\HeaderConsts;
use ZBateson\MailMimeParser\Header\Part\AddressPart;
use ZBateson\MailMimeParser\Message;

class ImportMail
{
    private AppServiceConfig $config;
    private FileStore $store;
    private MatrixClient $client;

    public function __construct(AppServiceConfig $config, FileStore $store, MatrixClient $client)
    {
        $this->config = $config;
        $this->store = $store;
        $this->client = $client;
    }

    public function run()
    {
        // This process one mail at a time because it is expected to run offent
        $mail = Mail::getBy(['action' => Mail::ActionImport, 'fail_code' => 0])->current();
        if (!$mail) {
            // Retry failed imports
            $mail = Mail::getBy(['action' => Mail::ActionImport])->current();
            if (!$mail) {
                return;
            }
        }
        try {
            $this->import($mail);
            $mail->destroy($this->store);
        } catch (Throwable $t) {
            Log::error($t);
            $mail->fail_code = $t->getCode();
            $mail->save();
        }
    }

    private function import(Mail $mail)
    {
        $fileInfo = $mail->getFileInfo($this->store);
        $account = Account::getByUid($mail->account_uid);
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

        //TODO parse to stdclass
        $this->to = $this->message->getHeader(HeaderConsts::TO);
        $this->ts = $this->message->getHeader(HeaderConsts::DATE);
        if (empty($this->to) || empty($this->ts) || empty($this->message->getHeader(HeaderConsts::FROM))) {
            throw new Exception('File is not valid', 1);
        }

        $this->subject = $this->message->getHeader(HeaderConsts::SUBJECT);
        if (is_object($this->subject)) {
            $this->subject = $this->subject->getValue();
        }

        $idx = strrpos($this->subject, ':');
        if ($idx !== false) {
            $this->subject = trim(substr($this->subject, $idx + 1));
        }
        if (empty($this->subject) && !empty($this->ts)) {
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
