<?php

namespace Karma;

use Gaufrette\Filesystem;
use Karma\Filters\FileFilterIterator;

class Finder
{
    private
        $fs;

    public function __construct(Filesystem $fs)
    {
        $this->fs = $fs;
    }

    public function findFiles($regex)
    {
        return new FileFilterIterator(
            new \ArrayIterator($this->fs->keys()),
            $regex,
            $this->fs
        );
    }
}
