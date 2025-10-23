<?php

namespace Karma\Filesystem\Adapters;

use Gaufrette\Adapter;
use Gaufrette\Util;

final class Memory implements Adapter, Adapter\MimeTypeProvider
{
    protected array
        $files = [];

    public function __construct(array $files = [])
    {
        $this->setFiles($files);
    }

    public function setFiles(array $files): void
    {
        $this->files = [];
        foreach ($files as $key => $file)
        {
            if (!is_array($file))
            {
                $file = ['content' => $file];
            }

            $file = array_merge([
                'content' => null,
                'mtime' => null,
            ], $file);

            $this->setFile($key, $file['content'], $file['mtime']);
        }
    }

    public function setFile(string $key, ?string $content = null, ?int $mtime = null): void
    {
        if(null === $mtime)
        {
            $mtime = time();
        }

        $this->files[$key] = [
            'content' => (string) $content,
            'mtime' => (integer) $mtime,
        ];
    }

    public function read($key)
    {
        return $this->files[$key]['content'];
    }

    public function rename($sourceKey, $targetKey): bool
    {
        $content = $this->read($sourceKey);
        $this->delete($sourceKey);

        return (bool) $this->write($targetKey, $content);
    }

    public function write($key, $content, ?array $metadata = null): bool|int
    {
        $this->files[$key]['content'] = $content;
        $this->files[$key]['mtime'] = time();

        return Util\Size::fromContent($content);
    }

    public function exists($key): bool
    {
        return array_key_exists($key, $this->files);
    }

    public function keys(): array
    {
        return array_keys($this->files);
    }

    public function mtime($key)
    {
        return $this->files[$key]['mtime'] ?? false;
    }

    public function delete($key): true
    {
        unset($this->files[$key]);
        clearstatcache();

        return true;
    }

    public function isDirectory($path): false
    {
        return false;
    }

    public function mimeType($key)
    {
        throw new \RuntimeException("Not implemented");
    }
}
