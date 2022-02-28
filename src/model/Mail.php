<?php

namespace bronsted;

use DateTime;
use Exception;
use SplFileInfo;
use stdClass;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;
use ZBateson\MailMimeParser\Message;

class Mail extends DbObject
{
    const ActionImport = 1;
    const ActionSend = 2;

    protected ?string $id;
    protected ?string $file_id;
    protected ?int $fail_code;
    protected ?int $action;
    protected ?int $taken;
    protected DateTime $last_try;
    protected ?int $account_uid;

    public function __construct(?string $id = null, ?string $file_id = null, ?int $action = null, ?int $account_uid = null)
    {
        parent::__construct();
        $this->id = $id;
        $this->file_id = $file_id;
        $this->action = $action;
        $this->account_uid = $account_uid;
        $this->fail_code = 0;
        $this->taken = 0;
        $this->last_try = new DateTime();
    }

    public function getFileInfo(FileStore $store): SplFileInfo
    {
        return $store->getFileInfo($this->file_id);
    }

    public function getAccountData(AppServiceConfig $config): AccountData
    {
        $account = Account::getByUid($this->account_uid);
        return $account->getAccountData($config);
    }

    public function getMessage(FileStore $store)
    {
        if ($this->action == self::ActionImport) {
            return Message::from($this->getFileInfo($store)->openFile(), true);
        }
        $info = $this->getFileInfo($store);
        return new RawMessage($info->openFile('r')->fread($info->getSize()));
    }

    public function save(): void
    {
        $this->validate();
        parent::save();
    }

    public function destroy(Filestore $store): void
    {
        $store->remove($this->file_id);
        parent::delete();
    }

    public function failed(int $code)
    {
        $this->taken = 0;
        $this->fail_code = $code;
        $this->last_try = new DateTime();
        $this->save();
    }

    public static function popImport()
    {
        $mail = Mail::getBy(['action' => Mail::ActionImport, 'fail_code' => 0, 'taken' => 0])->current();
        if ($mail) {
            $mail->taken = 1;
            $mail->save();
        }
        return $mail;
    }

    public static function createFromMail(Account $account, FileStore $store, string $message): Mail
    {
        $mail = new Mail();
        $mail->id = uniqid();
        $mail->file_id = $mail->id . '.mime';
        $mail->account_uid = $account->uid;
        $mail->action = self::ActionImport;
        $store->write($mail->file_id, $message);
        $mail->save();
        return $mail;
    }

    public static function createFromEvent(MatrixClient $client, AppServiceConfig $config, Http $http, Filestore $store, stdClass $event): Mail
    {
        $account = Account::getOneBy(['user_id' => $event->sender]);
        $message = self::fromEvent($client, $config, $account->getAccountData($config), $http, $event);

        $mail = new Mail();
        $mail->id = $event->event_id;
        $mail->file_id = uniqid() . '.mime';
        $mail->account_uid = $account->uid;
        $mail->action = self::ActionSend;
        $store->write($mail->file_id, $message);
        $mail->save();
        return $mail;
    }

    private static function fromEvent(MatrixClient $client, AppServiceConfig $config, AccountData $accountData, Http $http, stdClass $event): string
    {
        $room = Room::getById($client, $config, $event->room_id);

        $mail = new Email();
        $mail->from(new Address($accountData->email, $accountData->user_name))->subject($room->getName());

        $to = [];
        foreach($room->getMembers() as $recipient) {
            $to[] = new Address($recipient->getEmail(), $recipient->getName());
        }
        $mail->to(...$to);

        $attachment = null;
        //TODO P2 better handling of url types https://matrix.org/docs/spec/client_server/r0.6.1#m-room-message-msgtypes
        if ($event->content->msgtype == 'm.text') {
            $mail->text($event->content->body ?? '');
            $mail->html($event->content->formatted_body ?? '');
        }
        else if (isset($event->content->url)) {
            $attachment = sys_get_temp_dir() . '/' . uniqid();
            file_put_contents($attachment, $http->getStream($event->content->url));
            $mail->attachFromPath($attachment);
        }
        else {
            throw new Exception("Can't handle message type: " . $event->content->msgtype);
        }

        $result = serialize($mail);

        // Attachment if any can only be removed after the email is serialized
        if ($attachment) {
            @unlink($attachment);
        }

        return $result;
    }

    private function validate()
    {
        foreach (['id', 'file_id', 'action', 'account_uid'] as $name) {
            if (empty($this->$name)) {
                throw new Exception("$name must not be empty");
            }
        }

        if (!in_array($this->action, [self::ActionImport, self::ActionSend])) {
            throw new Exception('Action not valid');
        }
    }
}