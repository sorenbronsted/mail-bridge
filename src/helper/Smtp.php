<?php

namespace bronsted;

use PHPMailer\PHPMailer\PHPMailer;

class Smtp
{
    private $mailer;

    public function open(ImapAccount $account)
    {
        $this->mailer = new PHPMailer(true);
        $this->mailer->isSMTP();
        $this->mailer->Host = $account->smtp_host;
        $this->mailer->Port = $account->smtp_port;
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
        $this->close();
    }

    public function close()
    {
        $this->mailer = null;
    }
}