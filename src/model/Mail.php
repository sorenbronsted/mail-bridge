<?php

namespace bronsted;

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
    protected ?int $account_uid;

    public function __construct(?string $id = null, ?string $file_id = null, ?int $action = null, ?int $account_uid = null)
    {
        parent::__construct();
        $this->id = $id;
        $this->file_id = $file_id;
        $this->action = $action;
        $this->account_uid = $account_uid;
        $this->fail_code = 0;
    }

    public function getFileInfo(FileStore $store): SplFileInfo
    {
        return $store->getFileInfo($this->file_id);
    }

    public function destroy(Filestore $store): void
    {
        $store->remove($this->file_id);
        parent::delete();
    }

    public static function createFromEvent(Http $http, Filestore $store, User $sender, DbCursor $recipients, string $subject, stdClass $event)
    {
        $email = new Email();
        $email->from(new Address($sender->email, $sender->name))->subject($subject);

        $to = [];
        foreach($recipients as $recipient) {
            $to[] = new Address($recipient->email, $recipient->name);
        }
        $email->to(...$to);

        $attachment = null;
        //TODO P2 better handling of url types https://matrix.org/docs/spec/client_server/r0.6.1#m-room-message-msgtypes
        if ($event->content->msgtype == 'm.text') {
            $email->text($event->content->body ?? '');
            $email->html($event->content->formatted_body ?? '');
        }
        else if (isset($event->content->url)) {
            $attachment = sys_get_temp_dir() . '/' . uniqid();
            file_put_contents($attachment, $http->getStream($event->content->url));
            $email->attachFromPath($attachment);
        }
        else {
            throw new Exception("Can't handle message type: " . $event->content->msgtype);
        }

        $account = Account::getOneBy(['user_uid' => $sender->uid]);
        $mail = new Mail();
        $mail->id = $event->event_id;
        $mail->file_id = uniqid() . '.mime';
        $mail->account_uid = $account->uid;
        $mail->action = self::ActionSend;
        $store->write($mail->file_id, serialize($email));
        $mail->save();

        // Attachment if any can only be removed after the email is serialized
        if ($attachment) {
            @unlink($attachment);
        }

        return $mail;
    }

    public function getAccountData(AppServiceConfig $config): AccountData
    {
        $account = Account::getByUid($this->account_uid);
        return $account->getAccountData($config);
    }

    public function getEmail(FileStore $store): RawMessage
    {
        $info = $this->getFileInfo($store);
        return new RawMessage($info->openFile('r')->fread($info->getSize()));
    }

    public function parse(FileStore $store): Message
    {
        return Message::from($this->getFileInfo($store)->openFile(), true);
    }
}