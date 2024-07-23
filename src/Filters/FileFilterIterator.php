<?php

declare(strict_types = 1);

namespace Karma\Filters;

use Gaufrette\Adapter;
use Gaufrette\Filesystem;

final class FileFilterIterator extends \FilterIterator implements \Countable
{
    private Adapter
        $fsAdapter;
    private string
        $regex;

    public function __construct(\Iterator $iterator, string $regex, Filesystem $fs)
    {
        parent::__construct($iterator);

        $this->regex = $regex;
        $this->fsAdapter = $fs->getAdapter();
    }

    public function accept(): bool
    {
        $filename = $this->getInnerIterator()->current();

        return is_string($filename)
            && $this->fsAdapter->isDirectory($filename) !== true
            && preg_match($this->regex, $filename);
    }

    public function count(): int
    {
        return iterator_count($this);
    }
}
