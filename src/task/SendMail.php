<?php

namespace bronsted;

use FastRoute\RouteParser\Std;
use SplFileInfo;
use stdClass;
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
            // TODO P1 retry in a round robin way by getting the oldest and update a timestamp on mail with last try
            // Retry failed mails
            $mail = Mail::getBy(['action' => Mail::ActionSend])->current();
            if (!$mail) {
                return;
            }
        }

        try {
            $this->smtp->send($mail, $this->config, $this->store);
            $mail->destroy($this->store);
        } catch (Throwable $t) {
            Log::error($t);
            $mail->fail_code = $t->getCode();
            $mail->save();
        }
    }
}
