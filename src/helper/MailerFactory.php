<?php

namespace bronsted;

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;

class MailerFactory
{
    public function create(string $dsn): MailerInterface
    {
        return new Mailer(Transport::fromDsn($dsn));
    }
}
