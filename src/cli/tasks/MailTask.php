<?php

namespace bronsted;

use Exception;
use Throwable;

/* Less secure apps needs be enabled to be able to login into gmail.com
   https://support.google.com/accounts/answer/6010255?hl=en
 */

class MailTask
{
    private AppServiceConfig $config;
    private FileStore $fileStore;
    private ImapCtrl  $imap;

    public function __construct(AppServiceConfig $config, FileStore $filestore, ImapCtrl $imap)
    {
        $this->config = $config;
        $this->fileStore = $filestore;
        $this->imap = $imap;
    }

    public function run(array $args)
    {
        if (file_exists($this->config->pidFile)) {
            Log::info('Allready running, bye');
            return;
        }
        try {
            $ok = @file_put_contents($this->config->pidFile, getmypid());
            if (!$ok) {
                throw new Exception('Writing lock file failed');
            }
            $this->send();
            $this->fetch();
            $this->import();
        } catch (Throwable $t) {
            Log::error($t);
        } finally {
            if (file_exists($this->config->pidFile)) {
                unlink($this->config->pidFile);
            }
        }
    }

    private function send()
    {
        $files = $this->fileStore->getFiles(FileStore::Outbox);
        foreach ($files as $fileInfo) {
            try {
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
        $files = $this->fileStore->getFiles(FileStore::Inbox);
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
