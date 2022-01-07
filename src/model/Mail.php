<?php

namespace bronsted;

use Exception;
use SplFileInfo;

class Mail extends DbObject
{
    const ActionImport = 1;
    const ActionSend = 2;

    protected ?string $id;
    protected ?string $file_id;
    protected ?int $fail_code;
    protected ?int $action;
    protected ?int $account_uid;

    public function __construct(?string $id = null, ?string $file_id = null, ?int $action = null, ?int $account_uid = null)
    {
        parent::__construct();
        $this->id = $id;
        $this->file_id = $file_id;
        $this->action = $action;
        $this->account_uid = $account_uid;
        $this->fail_code = 0;
    }

    public function getFileInfo(FileStore $store): SplFileInfo
    {
        return $store->getFileInfo($this->file_id);
    }

    public function destroy(Filestore $store): void
    {
        $store->remove($this->file_id);
        parent::delete();
    }
}
