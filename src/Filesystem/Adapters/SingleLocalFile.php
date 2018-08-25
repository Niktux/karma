<?php

declare(strict_types = 1);

namespace Karma\Filesystem\Adapters;

use Gaufrette\Adapter;

class SingleLocalFile implements Adapter
{
    private
        $filename,
        $adapter;

    public function __construct($filename, Adapter $adapter)
    {
        $this->filename = $filename;
        $this->adapter = $adapter;
    }

    public function read($key)
    {
        if(! $this->exists($key))
        {
            return false;
        }

        return $this->adapter->read($key);
    }

    public function write($key, $content)
    {
        return $this->adapter->write($key, $content);
    }

    public function exists($key)
    {
        return $key === $this->filename;
    }

    public function keys()
    {
        return [$this->filename];
    }

    public function mtime($key)
    {
        if(! $this->exists($key))
        {
            return false;
        }

        return $this->adapter->mtime($key);
    }

    public function delete($key)
    {
        throw new \RuntimeException('Not implemented yet : ' . __METHOD__);
    }

    public function rename($sourceKey, $targetKey)
    {
        throw new \RuntimeException('Not implemented yet : ' . __METHOD__);
    }

    public function isDirectory($key)
    {
        return false;
    }
}
