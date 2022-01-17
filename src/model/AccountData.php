<?php

namespace bronsted;

use stdClass;

class AccountData
{
    public string $imap_url = '';
    public string $smtp_host = '';
    public string $smtp_port = '';
    public string $email = '';
    public string $user_name = '';
    public string $password = '';

    public function __construct(stdClass $data = null)
    {
        if (empty($data)) {
            return;
        }
        $this->imap_url  = $data->imap_url;
        $this->smtp_host = $data->smtp_host;
        $this->smtp_port = $data->smtp_port;
        $this->email     = $data->email;
        $this->user_name = $data->user_name;
        $this->password  = $data->password;
    }
}
