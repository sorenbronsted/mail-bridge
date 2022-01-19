<?php

namespace bronsted;

use DateTime;
use Throwable;

class FetchMail
{
    private AppServiceConfig $config;
    private Imap $imap;
    private FileStore $store;

    public function __construct(AppServiceConfig $config, Imap $imap, FileStore $store)
    {
        $this->config = $config;
        $this->imap = $imap;
        $this->store = $store;
    }

    public function run()
    {
        $since = new DateTime("-5 min");
        $account = Account::getWhere(" updated < :since", ['since' => $since])->current();
        if (!$account) {
            return;
        }

        try {
            $this->fetch($account);
            $account->updated = new DateTime();
            $account->save();
        } catch (Throwable $t) {
            Log::error($t);
        }
    }

    private function fetch(Account $account)
    {
        $accountData = $account->getAccountData($this->config);
        $this->imap->open($accountData);

        // sort mailbox by date with newest first
        $this->imap->sort(SORTDATE, true);

        $max = $this->imap->count();
        for ($i = 1; $i <= $max; $i++) {
            $header = $this->imap->header($i);
            if ($header->udate < $account->updated->format('U')) {
                break;
            }
            $message = $this->imap->message($i);
            Mail::createFromMail($account, $this->store, $message);
        }
        $this->imap->close();
    }
}
