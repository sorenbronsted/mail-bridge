<?php

namespace bronsted;

use Exception;
use stdClass;

class Account extends ModelObject
{
    protected string $name = '';
    protected string $data = '';
    protected int $user_uid;

    public function getContent(AppServiceConfig $config): ?stdClass
    {
        if ($this->data) {
            return unserialize(Crypto::decrypt($this->data, $config->key));
        }
        return null;
    }

    public function setContent(AppServiceConfig $config, stdClass $data)
    {
        $this->validate($data);
        $this->data = Crypto::encrypt(serialize($data), $config->key);
    }

    private function validate(stdClass $data)
    {
        $rules = new stdClass();
        $rules->imap_url = FILTER_DEFAULT;
        $rules->smtp_host = FILTER_DEFAULT;
        $rules->smtp_port = FILTER_DEFAULT | FILTER_VALIDATE_INT;
        $rules->user = FILTER_DEFAULT;
        $rules->password = FILTER_DEFAULT;

        $result = filter_var_array((array)$data, (array)$rules);
        $test = array_filter(array_values($result), function($item) {
            return !empty($item);
        });
        if (count($test) != count((array)$rules)) {
            //TODO P1 which properties fails and send a validation exception
            throw new Exception('Imap data is not valid');
        }
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