<?php

namespace bronsted;

use Exception;
use stdClass;

class Imap
{
    private $connection = null;

    public function sort(int $by, bool $reverse)
    {
        imap_sort($this->connection, $by, $reverse);
    }

    public function count(): int
    {
        return imap_num_msg($this->connection);
    }

    public function header(int $i): stdClass
    {
        return (object)imap_fetch_overview($this->connection, $i);
    }

    public function message(int $i): string
    {
        return imap_fetchheader($this->connection, $i) . imap_body($this->connection, $i);
    }

    public function __destruct()
    {
        $this->close();
    }

    public function open(ImapAccount $account)
    {
        if ($this->connection == null) {
            $this->connection = imap_open($account->imap_server, $account->user, $account->password);
            if ($this->connection === false) {
                $error = imap_last_error();
                throw new Exception('imap_open failed: ' . $error);
            }
        }
    }

    public function close()
    {
        if ($this->connection) {
            imap_close($this->connection);
        }
    }

}
