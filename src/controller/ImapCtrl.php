<?php
namespace bronsted;

use DateTime;
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
    private Imap $mail;
    private File $file;
    private Smtp $smtp;

    public function __construct(AppServiceConfig $config, MatrixClient $client, Imap $mail, File $file, Smtp $smtp)
    {
        $this->config = $config;
        $this->client = $client;
        $this->mail   = $mail;
        $this->file   = $file;
        $this->smtp   = $smtp;

        $this->file->root($config->storeInbox);
    }

    public function fetch(Account $account, DateTime $stop)
    {
        $accountData = $account->getAccountData($this->config);
        $this->mail->open($accountData);

        // sort mailbox by date in reverse order
        $this->mail->sort(SORTDATE, true);

        $max = $this->mail->count();
        for($i = 1; $i <= $max; $i++) {
            $header = $this->mail->header($i);
            if ($header->udate < $stop->format('U')) {
                break;
            }
            $filename = uniqid() . '.mime';
            $message = $this->mail->message($i);
            $this->file->write($filename, $message);
        }
        $this->mail->close();
    }

    public function sendMessage(User $sender, DbCursor $recipients, string $subject, string $text, string $html = '')
    {
        $account = Account::getOneBy(['user_uid' => $sender->uid]);
        $accountData = $account->getAccountData($this->config);
        $this->smtp->open($accountData);
        $this->smtp->from($sender);
        $this->smtp->addRecipients($recipients);
        $this->smtp->subject($subject);
        $this->smtp->body($text, $html);
        $this->smtp->send();
    }

    public function import(Account $account, $fh)
    {
        $this->parse($fh);

        if (count($this->to->getAddresses()) > 1) {
            $room = $this->getOrCreateMultiUserRoom($account);
        } else {
            $room = $this->getOrCreateDirectUserRoom($account);
        }

        $this->client->send($room, $this->from, $this->message, $this->ts->getDateTime());
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
        $this->from = $this->getOrCreateUser($from);
    }

    private function getOrCreateUser(AddressPart $user): User
    {
        $result = null;
        try {
            $result = User::getOneBy(['email' => $user->getEmail()]);
        }
        catch(NotFoundException $e) {
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