<?php

namespace bronsted;

use Exception;
use stdClass;

class AccountData
{
    /**
     * The imap url which can be empty
     * @var string
     */
    public string $imap_url = '';
    public string $smtp_host = '';
    public string $smtp_port = '';
    public string $email = '';
    public string $user_name = '';
    /**
     * The password for imap and smtp which can be empty
     * @var string
     */
    public string $password = '';

    public function __construct(stdClass $data = null)
    {
        if (empty($data)) {
            return;
        }

        foreach(array_keys(get_class_vars(__CLASS__)) as $name) {
            $this->$name = isset($data->$name) ? $data->$name : null;
        }
        $this->validate();
    }

    public function validate()
    {
        $rules = new stdClass();
        $rules->smtp_host = FILTER_VALIDATE_DOMAIN;
        $rules->smtp_port = FILTER_VALIDATE_INT;
        $rules->email = FILTER_VALIDATE_EMAIL;
        $rules->user_name = FILTER_DEFAULT;

        $data = get_class_vars(__CLASS__);
        foreach(array_keys($data) as $name) {
            $data[$name] = $this->$name;
        }
        $result = filter_var_array($data, (array)$rules);
        $test = array_filter(array_values($result), function ($item) {
            return !empty($item);
        });
        if (count($test) != count((array)$rules)) {
            //TODO P2 which properties fails and send a validation exception
            throw new Exception('Data is not valid');
        }
    }
}
