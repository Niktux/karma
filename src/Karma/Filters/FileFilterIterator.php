<?php

namespace Karma\Filters;

class FileFilterIterator extends \FilterIterator implements \Countable
{
    private
        $regex;
    
    public function __construct(\Iterator $iterator, $regex)
    {
        parent::__construct($iterator);
        
        $this->regex = $regex;
    }        
    
    public function accept()
    {
        $filename = $this->getInnerIterator()->current();
        
        return is_string($filename) && preg_match($this->regex, $filename);
    }
    
    public function count()
    {
        return iterator_count($this);
    }
}