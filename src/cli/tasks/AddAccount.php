<?php

namespace bronsted;

use Slim\App;
use stdClass;

class AddAccount
{
    private AppServiceConfig $config;

    public function __construct(AppServiceConfig $config)
    {
        $this->config = $config;
    }

    public function run(array $args)
    {
        // {imap.gmail.com:993/imap/ssl}INBOX
        $accountData = new stdClass();
        $accountData->imap_url = ''; //'{imap.nowhere:993/imap/ssl}INBOX';
        $accountData->smtp_host = 'localhost'; // Mailhog
        $accountData->smtp_port = '8025';
        $accountData->email = 'foo@bar.com';
        $accountData->user_name = 'Foo Bar';
        $accountData->password = ''; //'1234';

        // Create og update account
        $userId = '@sb:syntest.lan'; // This must a user known in synapse
        $account = null;
        try {
            $account = Account::getOneBy(['user_id' => $userId]);
        }
        catch(NotFoundException $e) {
            $account = new Account();
        }
        $account->name = 'test';
        $account->state = Account::StateOk;
        $account->state_text = 'Ok';
        $account->user_id = $userId;
        $account->setAccountData($this->config, new AccountData($accountData));
        $account->save();
    }
}