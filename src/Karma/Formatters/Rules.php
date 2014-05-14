<?php

namespace Karma\Formatters;

use Karma\Formatter;

class Rules implements Formatter
{
    private
        $rules;
    
    public function __construct(array $rules)
    {
        $this->convertRules($rules);
    }
    
    private function convertRules(array $rules)
    {
        $this->rules = array();
        
        $mapping = array(
            '<true>' => true,
            '<false>' => false,
            '<null>' => null,
        );
        
        foreach($rules as $value => $result)
        {
            if(is_string($value) && array_key_exists($value, $mapping))
            {
                $value = $mapping[$value];
            }
            
            $this->rules[] = array($value, $result);
        }    
    }
    
    public function format($value)
    {
        foreach($this->rules as $rule)
        {
            list($expected, $result) = $rule;
            
            if($expected === $value)
            {
                $value = $result;
                break;
            }
        }
        
        return $value;
    }
}
