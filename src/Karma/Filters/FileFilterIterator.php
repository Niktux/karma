<?php

namespace Karma\Filters;

use Gaufrette\Filesystem;

class FileFilterIterator extends \FilterIterator implements \Countable
{
    private
        $fsAdapter,
        $regex;

    public function __construct(\Iterator $iterator, $regex, Filesystem $fs)
    {
        parent::__construct($iterator);

        $this->regex = $regex;
        $this->fsAdapter = $fs->getAdapter();
    }

    public function accept()
    {
        $filename = $this->getInnerIterator()->current();

        return is_string($filename)
            && $this->fsAdapter->isDirectory($filename) !== true
            && preg_match($this->regex, $filename);
    }

    public function count()
    {
        return iterator_count($this);
    }
}
