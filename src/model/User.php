<?php

namespace bronsted;

use Exception;

class User extends ModelObject
{
    const PUPPET_PREFIX = '@mail_';
    protected ?string $id;
    protected ?string $name;
    protected ?string $email;

    public function __construct(?string $name = null, ?string $email = null, ?string $domain = null, ?string $local_id = null)
    {
        $this->name = trim($name);
        $this->email = strtolower($email);

        if (empty($this->name)) {
            $this->name = $this->email;
        }

        if ($local_id) {
            $this->id = '@' . $local_id . ':' . $domain;
        }
        else {
            $this->calcId($domain);
        }
    }

    public function save(): void
    {
        if (empty($this->name)) {
            throw new Exception('Name can not be empty');
        }
        parent::save();
    }

    public function setEmailById(string $id)
    {
        if (!self::isPuppet($id)) {
            throw new Exception('Not a valid puppet id');
        }
        $this->id = $id;
        $this->calcEmail();
    }

    public function localId(): string
    {
        return substr($this->id, 1, strpos($this->id, ':') - 1);
    }

    public static function isPuppet($id): bool
    {
        return substr($id, 0, strlen(self::PUPPET_PREFIX)) == self::PUPPET_PREFIX;
    }

    public static function getNonePuppets(): DbCursor
    {
        $sql = "select u.* from user u where substr(id,0,6) != '" . self::PUPPET_PREFIX ."'";
        return self::getObjects($sql, []);
    }

    public static function create(string $name, string $email, string $domain): User
    {
        $user = new User($name, $email, $domain);
        $user->save();
        return $user;
    }

    private function calcId($domain)
    {
        if ($this->email == null) {
            return;
        }
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
        $this->email = $email;
    }
}
