<?php

namespace bronsted;

use Symfony\Component\Mailer\MailerInterface;

class Smtp
{
    private ?MailerInterface $mailer;
    private MailerFactory $factory;

    public function __construct(MailerFactory $factory)
    {
        $this->factory = $factory;
    }

    public function open(AccountData $accountData)
    {
        $dsn = sprintf(
            'smtp://%s:%s@%s:%s',
            $accountData->user,
            $accountData->password,
            $accountData->smtp_host,
            $accountData->smtp_port
        );
        $this->mailer = $this->factory->create($dsn);
    }

    public function close()
    {
        $this->mailer = null;
    }

    public function canConnect(AccountData $accountData)
    {
        //TODO P2 not shure if this actually verified anything
        $this->open($accountData);
        $this->close();
    }

    public function send(Mail $mail, AppServiceConfig $config, FileStore $store)
    {
        $accountData = $mail->getAccountData($config);
        $this->open($accountData);
        $email = $mail->getEmail($store);
        $this->mailer->send($email);
        $this->close();
    }
}