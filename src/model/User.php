<?php

namespace bronsted;

use stdClass;

class User extends ModelObject
{

    protected ?string $id;
    protected ?string $name;
    protected ?string $email;

    public function __construct(?string $name = null, ?string $email = null, ?string $domain = null, ?string $local_id = null)
    {
        $this->name = $name;
        $this->email = $email;
        if ($local_id) {
            $this->id = '@' . $local_id . ':' . $domain;
        }
        else {
            $this->calcId($domain);
        }
    }

    public function localId(): string
    {
        return substr($this->id, 1, strpos($this->id, ':') - 1);
    }

    public static function getOrCreate(Http $http, string $name, string $email): User
    {
        try {
            return self::getOneBy(['email' => $email]);
        }
        catch(NotFoundException $e) {
            // Ignore will be created
        }
        return self::create($http, $name, $email);
    }

    public static function create(Http $http, string $name, string $email): User
    {
        $user = new User($name, $email, $http->config->domain);

        // Create the user
        $url            = '/_matrix/client/r0/register';
        $data           = new stdClass();
        $data->type     = "m.login.application_service";
        $data->username = $user->localId();
        $http->post($url, $data);
        $user->save();

        $url               = '/_matrix/client/r0/profile/' . urlencode($user->id) . '/displayname?user_id=' . urlencode($user->id);
        $data              = new stdClass();
        $data->displayname = $user->name;
        $http->put($url, $data);
        return $user;
    }

    private function calcId($domain)
    {
        if ($this->email == null) {
            return;
        }
        $email = strtolower($this->email);
        $idx = strpos($email, '@');
        $email[$idx] = '/';
        $this->id = '@mail_' . $email . ':' . $domain;
    }
}
