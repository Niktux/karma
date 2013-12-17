<?php

namespace Karma;

use Gaufrette\Filesystem;
use Karma\Filters\FileSuffixFilterIterator;

class Finder
{
    private
        $fs;
    
    public function __construct(Filesystem $fs)
    {
        $this->fs = $fs;
    }
    
    public function findFiles($suffix)
    {
        return new FileSuffixFilterIterator(
            new \ArrayIterator($this->fs->keys()),
            $suffix
        );
    }
}