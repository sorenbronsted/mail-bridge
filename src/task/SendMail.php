<?php

namespace bronsted;

use SplFileInfo;
use Throwable;

class SendMail
{
    private AppServiceConfig $config;
    private FileStore $store;
    private Smtp $smtp;

    public function __construct(AppServiceConfig $config, FileStore $store, Smtp $smtp)
    {
        $this->config = $config;
        $this->store = $store;
        $this->smtp = $smtp;
    }

    public function run()
    {
        // Start with new mails
        $mail = Mail::getBy(['action' => Mail::ActionSend, 'fail_code' => 0])->current();
        if (!$mail) {
            // Retry failed mails
            $mail = Mail::getBy(['action' => Mail::ActionSend])->current();
            if (!$mail) {
                return;
            }
        }

        try {
            $this->send($mail);
            $mail->destroy($this->store);
        } catch (Throwable $t) {
            Log::error($t);
            $mail->fail_code = $t->getCode();
            $mail->save();
        }
    }

    public function send(Mail $mail)
    {
        $fileInfo = $mail->getFileInfo($this->store);
        $file = $fileInfo->openFile('r');
        $data = unserialize($file->fread($file->getSize()));

        $account = Account::getOneBy(['user_uid' => $data->sender->uid]);
        $data->accountData = $account->getAccountData($this->config);

        $this->smtp->sendByAccount($data);
    }
}
