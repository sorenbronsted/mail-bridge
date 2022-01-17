<?php

namespace bronsted;

use DateTime;
use Exception;
use stdClass;

class Account extends DbObject
{
    // Account verification states
    const StateNone = 0;
    const StateOk   = 1;
    const StateFail = 2;

    protected string   $name = '';
    protected string   $data = '';
    protected int      $state = self::StateNone;
    protected string   $state_text = 'Not verified';
    protected DateTime $updated;
    protected string   $user_id;


    public function save(): void
    {
        //Ensure updated has a date
        if ($this->uid == 0 && !isset($this->updated)) {
            $this->updated = new DateTime();
        }
        parent::save();
    }

    public function verify(AppServiceConfig $config, Imap $imap, Smtp $smtp)
    {
        $this->state = self::StateNone;
        try {
            $imap->canConnect($this->getAccountData($config));
            $smtp->canConnect($this->getAccountData($config));
            $this->state = self::StateOk;
            $this->state_text = 'Ok';
        }
        catch(Exception $e) {
            $this->state = self::StateFail;
            $this->state_text = $e->getMessage();
        }
        $this->save();
    }

    public function getAccountData(AppServiceConfig $config): ?AccountData
    {
        if ($this->data) {
            return unserialize(Crypto::decrypt($this->data, $config->key));
        }
        return null;
    }

    public function setAccountData(AppServiceConfig $config, AccountData $data): void
    {
        $this->validate($data);
        $this->data = Crypto::encrypt(serialize($data), $config->key);
    }

    private function validate(AccountData $data)
    {
        $rules = new stdClass();
        $rules->imap_url = FILTER_DEFAULT;
        $rules->smtp_host = FILTER_DEFAULT;
        $rules->smtp_port = FILTER_VALIDATE_INT;
        $rules->email = FILTER_VALIDATE_EMAIL;
        $rules->user_name = FILTER_DEFAULT;
        $rules->password = FILTER_DEFAULT;

        $result = filter_var_array((array)$data, (array)$rules);
        $test = array_filter(array_values($result), function ($item) {
            return !empty($item);
        });
        if (count($test) != count((array)$rules)) {
            //TODO P2 which properties fails and send a validation exception
            throw new Exception('Imap data is not valid');
        }
    }
}
