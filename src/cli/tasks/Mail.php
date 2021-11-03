<?php

namespace bronsted;

use DirectoryIterator;
use Exception;
use Throwable;

/* Less secure apps needs be enabled to be able to login
   https://support.google.com/accounts/answer/6010255?hl=en
 */

class Mail
{
    private FileStore $fileStore;
    private ImapCtrl  $imap;

    public function __construct(FileStore $filestore, ImapCtrl $imap)
    {
        $this->fileStore = $filestore;
        $this->imap = $imap;
    }

    public function run(array $args)
    {
        $lock = '/var/tmp/mxmail.pid';

        if (file_exists($lock)) {
            Log::info('Allready running, bye');
            return;
        }
        try {
            $ok = @file_put_contents($lock, getmypid());
            if (!$ok) {
                throw new Exception('Writing lock file failed');
            }
            $this->send();
            $this->fetch();
            $this->import();
        } catch (Throwable $t) {
            Log::error($t);
        } finally {
            if (file_exists($lock)) {
                unlink($lock);
            }
        }
    }

    private function send()
    {
        $files = $this->fileStore->getDir(FileStore::Outbox);
        foreach ($files as $fileInfo) {
            try {
                if ($fileInfo->isDir()) {
                    continue;
                }
                $this->imap->send($fileInfo);
                unlink($fileInfo->getPathname());
            } catch (Throwable $t) {
                Log::error($t);
                $this->fileStore->move($fileInfo, FileStore::FailSend);
            }
        }
    }

    private function fetch()
    {
        $accounts = Account::getAll();
        foreach ($accounts as $account) {
            try {
                $this->imap->fetch($account);
            } catch (Throwable $t) {
                Log::error($t);
            }
        }
    }

    private function import()
    {
        $files = $this->fileStore->getDir(FileStore::Inbox);
        foreach ($files as $fileInfo) {
            try {
                $this->imap->import($fileInfo);
                unlink($fileInfo->getPathname());
            } catch (Throwable $t) {
                Log::error($t);
                $this->fileStore->move($fileInfo, FileStore::FailImport);
            }
        }
    }
}
