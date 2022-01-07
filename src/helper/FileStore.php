<?php

namespace bronsted;

use DirectoryIterator;
use Exception;
use SplFileInfo;
use SplFileObject;
use Symfony\Component\Finder\Finder;

class FileStore
{
    private string $root;

    public function __construct(AppServiceConfig $config)
    {
        $this->root = $config->fileStoreRoot;
        $this->ensureDir();
    }

    public function getFileInfo(string $file): SplFileInfo
    {
        $filename = $this->root . '/' . $file;
        return new SplFileInfo($this->root . '/' . $file);
    }

    public function getFiles(): Finder
    {
        return Finder::create()->files()->in($this->root);
    }

    public function write(string $filename, string $content): void
    {
        $fileInfo = $this->getFileInfo($filename);
        $file = $fileInfo->openFile('w');
        $file->fwrite($content);
    }

    public function remove(string $name)
    {
        $file = $this->getFileInfo($name);
        $ok = @unlink($file->getPathname());
        if (!$ok) {
            throw new Exception("Remove file failed: " . $file->getPathname());
        }
    }

    public function cleanAll(): void
    {
        array_map('unlink', glob($this->root . '/*'));
    }

    private function ensureDir()
    {
        if (!file_exists($this->root)) {
            $ok = @mkdir($this->root, 0755, true);
            if (!$ok) {
                throw new Exception("Create dir failed: $this->root");
            }
        }
    }
}
