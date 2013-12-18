<?php

namespace Karma\Filters;

class FileSuffixFilterIterator extends \FilterIterator implements \Countable
{
    private
        $suffix;
    
    public function __construct(\Iterator $iterator, $suffix)
    {
        parent::__construct($iterator);
        
        $this->suffix = $suffix;
    }        
    
    public function accept()
    {
        $filename = $this->getInnerIterator()->current();
        
        return is_string($filename) && preg_match("~$this->suffix$~", $filename);
    }
    
    public function count()
    {
        return iterator_count($this);
    }
}