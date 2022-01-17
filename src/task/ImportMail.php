<?php

namespace bronsted;

use Exception;

use stdClass;
use Throwable;
use ZBateson\MailMimeParser\Header\HeaderConsts;
use ZBateson\MailMimeParser\Header\Part\AddressPart;
use ZBateson\MailMimeParser\Message;

/* Less secure apps needs be enabled to be able to login into gmail.com
   https://support.google.com/accounts/answer/6010255?hl=en
 */

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
        $result->to = $message->getHeader(HeaderConsts::TO);
        $result->ts = $message->getHeader(HeaderConsts::DATE);
        if (empty($result->to) || empty($result->ts) || empty($message->getHeader(HeaderConsts::FROM))) {
            throw new Exception('File is not valid', 1);
        }

        $result->subject = $message->getHeader(HeaderConsts::SUBJECT);
        if (is_object($result->subject)) {
            $result->subject = trim($result->subject->getValue());
        }

        $idx = strrpos($result->subject, ':');
        if ($idx !== false) {
            $result->subject = trim(substr($result->subject, $idx + 1));
        }

        if (empty($result->subject) && !empty($result->ts)) {
            $datetime = $result->ts->getDateTime();
            $result->subject = 'No subject ' . $datetime->format('Y-m-d H:i');
        }

        $result->alias = strtolower(str_replace(' ', '-', $result->subject));

        $from = $message->getHeader(HeaderConsts::FROM)->getAddresses()[0];
        $result->from = User::fromMail($from, $this->config->domain);
        return $result;
    }

    private function getOrCreateMultiUserRoom(Account $account, stdClass $header): Room
    {
        $room = null;
        try {
            $room = Room::getByAlias($this->client, $header->alias);
        } catch (NotFoundException $e) {
            $room = Room::create($this->client, $header->alias, $header->subject, $header->from, false);
        }

        $room->addUser($header->from, $account);
        foreach ($header->to->getAddresses() as $address) {
            $user = User::fromMail($address, $this->config->domain);
            $room->addUser($user, $account);
        }
        return $room;
    }

    private function getOrCreateDirectUserRoom(Account $account, stdClass $header): Room
    {
        $room = null;
        try {
            $room = Room::getByAlias($this->client, $header->from->getId());
        } catch (NotFoundException $e) {
            $room = Room::create($this->client, $header->from->getId(), $header->from->getName(), $header->from, true);
        }
        return $room;
    }
}
