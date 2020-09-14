<?php

declare(strict_types = 1);

namespace Karma;

use Gaufrette\Filesystem;
use Karma\Filters\FileFilterIterator;

class Finder
{
    private Filesystem
        $fs;

    public function __construct(Filesystem $fs)
    {
        $this->fs = $fs;
    }

    public function findFiles(string $regex): iterable
    {
        return new FileFilterIterator(
            new \ArrayIterator($this->fs->keys()),
            $regex,
            $this->fs
        );
    }
}
