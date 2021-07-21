<?php
namespace bronsted;

use DateTime;
use PHPMailer\PHPMailer\PHPMailer;
use ZBateson\MailMimeParser\Header\AddressHeader;
use ZBateson\MailMimeParser\Header\DateHeader;
use ZBateson\MailMimeParser\Header\HeaderConsts;
use ZBateson\MailMimeParser\Header\Part\AddressPart;
use ZBateson\MailMimeParser\Message;

class Imap
{
    private AddressHeader $to;
    private User $from;
    private MatrixClient $client;
    private Message $message;
    private DateHeader $ts;
    private AppServerConfig $config;

    public function __construct(AppServerConfig $config, MatrixClient $client)
    {
        $this->config = $config;
        $this->client = $client;
    }

    public function fetch(DateTime $stop)
    {
        $users = User::getNonePuppets();
        foreach($users as $user) {
            $account = Account::getOneBy(['user_uid' => $user->uid])->getContent($this->config);
            $connection = imap_open($account->server, $account->user, $account->password);

            if (!file_exists($this->config->storeInbox)) {
                mkdir($this->config->storeInbox, 0664, true);
            }

            // sort mailbox by date in reverse order
            imap_sort($connection, SORTDATE, true);

            $max = imap_num_msg($connection);

            for($i = 1; $i <= $max; $i++) {
                $header = (object)imap_fetch_overview($connection, $i);
                if ($header->udate < $stop->format('U')) {
                    break;
                }
                $filename = uniqid() . '.mime';
                $source = imap_fetchheader($connection, $i) . imap_body($connection, $i);
                file_put_contents($this->config->storeInbox . '/' . $filename, $source);
            }
            imap_close($connection);
        }
    }

    public function send(User $sender, DbCursor $recipients, string $subject, string $message, array $attachments = [])
    {
        //TODO config is per user
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'localhost';
        $mail->Port = 1025;

        $mail->setFrom($sender->email, $sender->name);
        foreach($recipients as $recipient) {
            $mail->addAddress($recipient->email, $recipient->name);
        }
        $mail->Subject = $subject;
        $mail->Body = $message;

        $mail->send();
    }

    public function import($fh)
    {
        $this->parse($fh);

        if (count($this->to->getAddresses()) > 1) {
            $room = $this->getOrCreateMultiUserRoom();
        } else {
            $room = $this->getOrCreateDirectUserRoom();
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

    private function getOrCreateMultiUserRoom(): Room
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
            $room = Room::create($id, $this->subject, $this->from);
            //TODO $this->user shall have the same power as the creator
            foreach ($this->to->getAddresses() as $address) {
                $member = $this->getOrCreateUser($address);
                $this->addUser($room, $member);
            }
        }
        return $room;
    }

    private function getOrCreateDirectUserRoom(): Room
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
            $room = Room::create($id, $name, $this->from);
        }
        return $room;
    }
}