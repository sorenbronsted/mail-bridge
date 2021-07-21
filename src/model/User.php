<?php

namespace bronsted;

class User extends ModelObject
{

    protected ?string $id;
    protected ?string $name;
    protected ?string $email;

    public function __construct(?string $name = null, ?string $email = null, ?string $domain = null, ?string $local_id = null)
    {
        $this->name = $name;
        $this->email = strtolower($email);
        if ($local_id) {
            $this->id = '@' . $local_id . ':' . $domain;
        }
        else {
            $this->calcId($domain);
        }
    }

    public function setEmailById(string $id)
    {
        $this->id = $id;
        $this->calcEmail();
    }

    public function localId(): string
    {
        return substr($this->id, 1, strpos($this->id, ':') - 1);
    }

    public static function getNonePuppets(): DbCursor
    {
        $sql = "select u.* from user u where substr(id,0,6) != '@mail_'";
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
        $this->id = '@mail_' . $email . ':' . $domain;
    }

    private function calcEmail()
    {
        if ($this->id == null) {
            return;
        }
        // id example: @mail_me/somewhere.net@syntest.lan
        $parts = explode('@', substr($this->id, strlen('@mail_')));
        $idx = strpos($parts[0], '/');
        $parts[0][$idx] = '@';
        $this->email = $parts[0];
    }
}
