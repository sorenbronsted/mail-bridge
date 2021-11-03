<?php

namespace bronsted;

use DirectoryIterator;
use Exception;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

class FileStore
{
    const Inbox = 0;
    const Outbox = 1;
    const FailImport = 2;
    const FailSend = 3;

    private string $root;
    private static array $dirs = ['in', 'out', 'fail/import', 'fail/send'];

    public function __construct($root)
    {
        $this->root = $root;
        $this->ensureDirs();
    }

    public function getDir(int $id): Finder
    {
        return Finder::create()->files()->in($this->dirname($id));
    }

    public function write(int $id, string $filename, string $content): void
    {
        $dirname = $this->dirname($id);
        file_put_contents($dirname . '/' . $filename, $content);
    }

    public function move(SplFileInfo $file, int $toId)
    {
        $to = $this->dirname($toId);
        rename($file->getPathname(), $to . '/' . $file->getFilename());
    }

    public function cleanAll(): void
    {
        foreach(array_keys(self::$dirs) as $id) {
            $dirname = $this->dirname($id);
            array_map('unlink', glob($dirname . '/*'));
        }
    }

    private function dirname(int $id): string
    {
        if ($id < 0 || $id >= count(self::$dirs)) {
            throw new Exception('Unknown dir id: ' . $id );
        }
        return $this->root . '/' . self::$dirs[$id];
    }

    private function ensureDirs()
    {
        foreach(array_keys(self::$dirs) as $id) {
            $dirname = $this->dirname($id);
            if (!file_exists($dirname)) {
                mkdir($dirname, 0755, true);
            }
        }
    }
}
