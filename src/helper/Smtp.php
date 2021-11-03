<?php

namespace bronsted;

use PHPMailer\PHPMailer\PHPMailer;
use stdClass;

class Smtp
{
    private $mailer;

    public function __construct(PHPMailer $mailer)
    {
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

    public function sendByAccount(AppServiceConfig $config, stdClass $data)
    {
        $accountData = $data->account->getAccountData($config);
        $this->open($accountData);
        $this->from($data->sender);
        $this->subject($data->subject);
        $this->body($data->text, $data->html);
        foreach($data->recipients as $recipient) {
            $this->mailer->addAddress($recipient->email, $recipient->name);
        }
        $this->send();
    }
}