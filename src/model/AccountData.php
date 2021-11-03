<?php

namespace bronsted;

use stdClass;

class AccountData
{
    public string $imap_url = '';
    public string $smtp_host = '';
    public string $user = '';
    public string $password = '';

    public function __construct(stdClass $data = null)
    {
        if (empty($data)) {
            return;
        }
        $this->imap_url  = $data->imap_url;
        $this->smtp_host = $data->smtp_host;
        $this->user      = $data->user;
        $this->password  = $data->password;
    }
}
