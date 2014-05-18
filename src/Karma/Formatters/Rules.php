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
            '<string>' => function($value) {
                return is_string($value);
            }
        );
        
        foreach($rules as $value => $result)
        {
            $value = trim($value);
            
            if(is_string($value) && array_key_exists($value, $mapping))
            {
                if($value === '<string>')
                {
                    $result = function ($value) use ($result) {
                        return str_replace('<string>', $value, $result);    
                    };
                }
                
                $value = $mapping[$value];
            }
            
            $this->rules[] = array($value, $result);
        }    
    }
    
    public function format($value)
    {
        foreach($this->rules as $rule)
        {
            list($ruleTrigger, $result) = $rule;
            
            $expected = ($ruleTrigger === $value);
            if($ruleTrigger instanceof \Closure)
            {
                $expected = $ruleTrigger($value);
            }
            
            if($expected === true)
            {
                if($result instanceof \Closure)
                {
                    return $result($value);    
                }
                
                return $result;
            }
        }
        
        return $value;
    }
}
