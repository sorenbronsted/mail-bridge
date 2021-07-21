<?php

namespace bronsted;

use stdClass;

class Account extends ModelObject
{
    protected string $data;
    protected int $user_uid;

    public function getContent(AppServerConfig $config): stdClass
    {
        return json_decode(Crypto::decrypt($this->data, $config->key));
    }

    public function setContent(AppServerConfig $config, stdClass $data)
    {
        $this->data = Crypto::encrypt(json_encode($data), $config->key);
        $this->save();
    }
}