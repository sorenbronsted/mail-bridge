<?php

namespace bronsted;

use Exception;
use ZBateson\MailMimeParser\Header\Part\AddressPart;

class User
{
    const PUPPET_PREFIX = '@mail_';
    private string $id;
    private string $name;
    private string $email;

    public function __construct(string $id, string $name, string $domain = '')
    {
        if (empty($id)) {
            throw new Exception("id must not be empty");
        }
        if (empty($name)) {
            throw new Exception("name must not be empty");
        }
        $this->name = $name;
        if (self::isPuppet($id)) {
            $this->id = $id;
            $this->calcEmail();
        }
        else if ($id[0] == '@') {
            $this->id = $id;
            $this->email = '';
        }
        else {
            if (empty($domain)) {
                throw new Exception('domain must not be empty');
            }
            self::validateEmail($id);
            $this->email = $id;
            $this->calcId($domain);
        }
    }

    public static function fromMail(AddressPart $address, $domain)
    {
        return new self(
            strtolower($address->getEmail()),
            trim($address->getName()) ?? '',
            $domain
        );
    }

    public static function fromId(string $id, string $name)
    {
        return new self(
            $id,
            trim($name) ?? ''
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function localId(): string
    {
        return substr($this->id, 1, strpos($this->id, ':') - 1);
    }

    public static function isPuppet(string $id): bool
    {
        return substr($id, 0, strlen(self::PUPPET_PREFIX)) == self::PUPPET_PREFIX;
    }

    public static function validateEmail(string $email)
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new Exception('email is not valid');
        }
    }

    private function calcId($domain)
    {
        $email = $this->email;
        $idx = strpos($email, '@');
        $email[$idx] = '/';
        $this->id = self::PUPPET_PREFIX . $email . ':' . $domain;
    }

    private function calcEmail()
    {
        // id example: @mail_me/somewhere.net:syntest.lan
        $email = substr($this->localId(), strlen(self::PUPPET_PREFIX) - 1);
        $idx = strpos($email, '/');
        $email[$idx] = '@';
        self::validateEmail($email);
        $this->email = $email;
    }
}
