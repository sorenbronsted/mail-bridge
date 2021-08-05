<?php

namespace bronsted;

use Exception;
use stdClass;

class ImapAccount
{
    public string $imap_url;
    public string $smtp_host;
    public string $smtp_port;
    public string $user;
    public string $password;

    public static function parse(array $data): ImapAccount
    {
        self::validate($data);
        $account = new ImapAccount();
        foreach($data as $name => $value) {
            $account->$name = $value;
        }
        return $account;
    }

    public static function validate(array $data)
    {
        $rules = new stdClass();
        $rules->imap_url = FILTER_DEFAULT;
        $rules->smtp_host = FILTER_DEFAULT;
        $rules->smtp_port = FILTER_DEFAULT | FILTER_VALIDATE_INT;
        $rules->user = FILTER_DEFAULT;
        $rules->password = FILTER_DEFAULT;

        $result = filter_var_array($data, (array)$rules);
        $test = array_filter(array_values($result), function($item) {
            return !empty($item);
        });
        if (count($test) != count((array)$rules)) {
            throw new Exception('Imap data is not valid');
        }
    }
}