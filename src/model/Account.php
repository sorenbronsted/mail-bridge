<?php

namespace bronsted;

class Account extends ModelObject
{
    protected string $data = '';
    protected int $user_uid;
    // TODO P2 an account can have more than 1 (eg. soren@bronsted.dk and sorenbronsted@gmail.com)

    public function getContent(AppServiceConfig $config): ?ImapAccount
    {
        if ($this->data) {
            return unserialize(Crypto::decrypt($this->data, $config->key));
        }
        return null;
    }

    public function setContent(AppServiceConfig $config, ImapAccount $data)
    {
        $this->data = Crypto::encrypt(serialize($data), $config->key);
    }

    public static function exists(User $user): bool
    {
        try {
            self::getOneBy(['user_uid' => $user->uid]);
            return true;
        }
        catch(NotFoundException $e) {
            // ignore
        }
        return false;
    }
}