<?php

namespace bronsted;

use DateTime;
use Throwable;

class FetchMail
{
    private AppServiceConfig $config;
    private Imap $imap;
    private FileStore $fileStore;

    public function __construct(AppServiceConfig $config, Imap $imap, FileStore $fileStore)
    {
        $this->config = $config;
        $this->imap = $imap;
        $this->fileStore = $fileStore;
    }

    public function run()
    {
        $since = new DateTime("-5 min");
        $account = Account::getWhere(" updated < :since", ['since' => $since], [])->current();
        if (!$account) {
            return;
        }
        try {
            $this->fetch($account);
        } catch (Throwable $t) {
            Log::error($t);
        } finally {
            $account->updated = new DateTime();
            $account->save();
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
            //TODO P1 use Mail
            $filename = $account->uid . '-' . uniqid() . '.mime';
            $message = $this->imap->message($i);
            $this->fileStore->write($filename, $message);
        }
        $this->imap->close();
    }
}
