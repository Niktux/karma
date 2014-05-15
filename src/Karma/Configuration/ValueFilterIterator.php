<?php

namespace Karma\Configuration;

class ValueFilterIterator extends \FilterIterator
{
    use \Karma\Configuration\FilterInputVariable;
    
    const
        FILTER_WILDCARD = '*',
        ESCAPED_WILDCARD = '**';
    
    private
        $isRegex,
        $filter;
    
    public function __construct($filter, \Iterator $iterator)
    {
        parent::__construct($iterator);
        
        $filter = $this->filterValue($filter);
        
        $this->isRegex = false;
        if(stripos($filter, self::FILTER_WILDCARD) !== false)
        {
            $this->isRegex = true;
            $filter = $this->convertToRegex($filter);
        }
        
        $this->filter = $filter;
    }
    
    public function accept()
    {
        $value = $this->getInnerIterator()->current();
        
        if($this->isRegex === true)
        {
            return preg_match($this->filter, $value);
        }
        
        return $value === $this->filter;
    }
    
    private function convertToRegex($filter)
    {
        $filter = $this->escapeRegexSpecialCharacters($filter);
        
        $escapedWildcardMarker = '@@@KARMA:ESCAPED_WILDCARD@@@';
        
        $filter = str_replace(self::ESCAPED_WILDCARD, $escapedWildcardMarker, $filter);
        $filter = str_replace(self::FILTER_WILDCARD, '.*', $filter);
        $filter = str_replace($escapedWildcardMarker, "\\*", $filter);
        
        return "~^$filter$~";
    }
    
    private function escapeRegexSpecialCharacters($filter)
    {
        return strtr($filter, array(
            '.' => '\.',
            '?' => '\?',
            '+' => '\+',
            '[' => '\[',
            ']' => '\]',
            '(' => '\(',
            ')' => '\)',
            '{' => '\{',
            '}' => '\}',
        ));
    }
}