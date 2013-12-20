<?php

namespace Karma\Configuration;

class ValueFilter
{
    use \Karma\Configuration\FilterInputVariable;
    
    const
        FILTER_WILDCARD = '*';
    
    private
        $values;
    
    public function __construct(array $values)
    {
        $this->values = $values;
    }
    
    public function filter($filter)
    {
        $filter = $this->filterValue($filter);

        return array_filter($this->values, $this->createFilterFunction($filter));
    }
    
    private function createFilterFunction($filter)
    {
        if(stripos($filter, self::FILTER_WILDCARD) !== false)
        {
            $filter = $this->convertToRegex($filter);
    
            return function($value) use ($filter){
                return preg_match($filter, $value);
            };
        }
    
        return function($value) use ($filter) {
            return $filter === $value;
        };
    }
    
    private function convertToRegex($filter)
    {
        $filter = $this->escapeRegexSpecialCharacters($filter);
        $filter = str_replace(self::FILTER_WILDCARD, '.*', $filter);
    
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