<?php

namespace bronsted;

use PHPMailer\PHPMailer\PHPMailer;
use Exception;
use SplFileObject;
use stdClass;

class Smtp
{
    private PHPMailer $mailer;
    private Http $http;

    public function __construct(PHPMailer $mailer, Http $http)
    {
        $this->http = $http;
        $this->mailer = $mailer;
        $this->mailer->isSMTP();
        $this->mailer->SMTPAuth   = true;
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $this->mailer->Port       = 465;
    }

    public function open(AccountData $accountData)
    {
        $this->mailer->Username = $accountData->user;
        $this->mailer->Password = $accountData->password;
        $this->mailer->Host = $accountData->smtp_host;
    }

    public function from(User $user)
    {
        $this->mailer->setFrom($user->email, $user->name);
    }

    public function addRecipients(DbCursor $recipients)
    {
        foreach($recipients as $recipient) {
            $this->mailer->addAddress($recipient->email, $recipient->name);
        }
    }

    public function subject(string $subject)
    {
        $this->mailer->Subject = $subject;
    }

    public function body(string $text, string $html = '')
    {
        if (empty($html)) {
            $this->mailer->Body = $text;
            $this->mailer->isHtml(false);
        }
        else {
            $this->mailer->Body = $html;
            $this->mailer->AltBody = $text;
            $this->mailer->isHtml(true);
        }
    }

    public function send()
    {
        $this->mailer->send();
    }

    public function canConnect(AccountData $accountData)
    {
        // Throws an exception if not working
        $this->open($accountData);
        $this->mailer->smtpConnect();
    }

    public function addAttachment(string $name, string $path)
    {
        $stream = $this->http->getStream($path);
        $file = new SplFileObject('/tmp/' . uniqid(), 'w');
        $file->fwrite($stream);
        $this->mailer->addAttachment($file->getPathname(), $name);
    }

    public function sendByAccount(stdClass $data)
    {
        $this->open($data->accountData);
        $this->from($data->sender);
        $this->subject($data->subject);

        if ($data->event->content->msgtype == 'm.text') {
            $this->body($data->event->content->body, $data->event->content->formatted_body);
        }
        else if (isset($data->event->content->url)) {
            //TODO P2 better handling of url types https://matrix.org/docs/spec/client_server/r0.6.1#m-room-message-msgtypes
            $this->addAttachment($data->event->content->body, $data->event->content->url);
        }
        else {
            throw new Exception("Can't handle message type: " . $data->event->content->msgtype);
        }

        foreach($data->recipients as $recipient) {
            $this->mailer->addAddress($recipient->email, $recipient->name);
        }
        $this->send();
    }
}