<?php

namespace bronsted;

use Exception;
use stdClass;

class Imap
{
    private $connection = null;

    public function sort(int $by, bool $reverse)
    {
        $this->isOk(imap_sort($this->connection, $by, $reverse));
    }

    public function count(): int
    {
        return $this->isOk(imap_num_msg($this->connection));
    }

    public function header(int $i): stdClass
    {
        return (object)$this->isOk(imap_fetch_overview($this->connection, $i));
    }

    public function message(int $i): string
    {
        return $this->isOk(imap_fetchheader($this->connection, $i)) . $this->isOk(imap_body($this->connection, $i));
    }

    public function __destruct()
    {
        $this->close();
    }

    public function open(AccountData $accountData)
    {
        if ($this->connection == null) {
            $this->connection = @imap_open($accountData->imap_url, $accountData->user, $accountData->password);
            if ($this->connection === false) {
                $error = imap_last_error();
                throw new Exception($error, 401);
            }
        }
    }

    public function close()
    {
        if ($this->connection) {
            imap_close($this->connection);
            $this->connection = null;
        }
    }

    public function canConnect(AccountData $accountData)
    {
        // Throws an exception if not working
        $this->open($accountData);
        $this->close();
    }

    private function isOk($value)
    {
        if ($value === false) {
            throw new Exception('Imap operation failed');
        }
        return $value;
    }
}
