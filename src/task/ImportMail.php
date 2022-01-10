<?php

namespace bronsted;

use Exception;

use stdClass;
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
        $message = $mail->parse($this->store);
        $header = $this->parse($message);
        $account = Account::getByUid($mail->account_uid);

        if (count($header->to->getAddresses()) > 1) {
            $room = $this->getOrCreateMultiUserRoom($account, $header);
        } else {
            $room = $this->getOrCreateDirectUserRoom($account, $header);
        }

        $this->client->send($room, $header->from, $message, $header->ts->getDateTime());
    }

    private function parse(Message $message): stdClass
    {
        $result = new stdClass();

        //TODO parse to stdclass
        $result->to = $message->getHeader(HeaderConsts::TO);
        $result->ts = $message->getHeader(HeaderConsts::DATE);
        if (empty($result->to) || empty($result->ts) || empty($message->getHeader(HeaderConsts::FROM))) {
            throw new Exception('File is not valid', 1);
        }

        $result->subject = $message->getHeader(HeaderConsts::SUBJECT);
        if (is_object($result->subject)) {
            $result->subject = $result->subject->getValue();
        }

        $idx = strrpos($result->subject, ':');
        if ($idx !== false) {
            $result->subject = trim(substr($result->subject, $idx + 1));
        }
        if (empty($result->subject) && !empty($result->ts)) {
            $datetime = $result->ts->getDateTime();
            $result->subject = 'No subject ' . $datetime->format('Y-m-d H:i');
        }

        $from = $message->getHeader(HeaderConsts::FROM)->getAddresses()[0];
        $result->from = $this->getOrCreateUser($from, $result);
        return $result;
    }

    private function getOrCreateUser(AddressPart $user, stdClass $header): User
    {
        $result = null;
        try {
            $result = User::getOneBy(['email' => $user->getEmail()]);
        } catch (NotFoundException $e) {
            $result = User::create($user->getName(), $user->getEmail(), $this->config->domain);
            $this->client->createUser($result, $header->ts->getDateTime());
        }
        return $result;
    }

    private function addUser(Room $room, User $user, object $ts)
    {
        $this->client->invite($room, $user, $ts->getDateTime());
        $this->client->join($room, $user, $ts->getDateTime());
        $member = new Member($room->uid, $user->uid);
        $member->save();
    }

    private function getOrCreateMultiUserRoom(Account $account, stdClass $header): Room
    {
        $room = null;
        try {
            $room = Room::getOneBy(['name' => $header->subject]);
            if (!$room->hasMember($header->from)) {
                $this->addUser($room, $header->from, $header->ts);
            }
            foreach ($header->to->getAddresses() as $address) {
                $member = $this->getOrCreateUser($address, $header);
                if (!$room->hasMember($member)) {
                    $this->addUser($room, $member, $header->ts);
                }
            }
        } catch (NotFoundException $e) {
            $id = $this->client->createRoom($header->subject, $header->from, $header->ts->getDateTime());
            $room = Room::create($id, $header->subject, $header->from, $account);
            foreach ($header->to->getAddresses() as $address) {
                $member = $this->getOrCreateUser($address, $header);
                $this->addUser($room, $member, $header->ts);
            }
        }

        // Ensure that current account is allways a member
        $user = User::getByUid($account->user_uid);
        if (!$room->hasMember($user)) {
            $room->join($user);
        }
        return $room;
    }

    private function getOrCreateDirectUserRoom(Account $account, stdClass $header): Room
    {
        $room = null;
        $name = null;
        try {
            $name = $header->from->name;
            if (empty($name)) {
                $name = $header->from->email;
            }
            $room = Room::getOneBy(['name' => $name]);
        } catch (NotFoundException $e) {
            $id = $this->client->createRoom($name, $header->from, $header->ts->getDateTime());
            $room = Room::create($id, $name, $header->from, $account);
            $user = User::getByUid($account->user_uid);
            $this->addUser($room, $user, $header->ts);
        }
        return $room;
    }
}
