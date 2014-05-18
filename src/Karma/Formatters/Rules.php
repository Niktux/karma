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
    
    private function getSpecialValuesMappingTable()
    {
        return array(
            '<true>' => true,
            '<false>' => false,
            '<null>' => null,
            '<string>' => function($value) {
                return is_string($value);
            }
        );
    }
    
    private function convertRules(array $rules)
    {
        $this->rules = array();
        $mapping = $this->getSpecialValuesMappingTable();
        
        foreach($rules as $value => $result)
        {
            $value = trim($value);
            
            if(is_string($value) && array_key_exists($value, $mapping))
            {
                $result = $this->handleStringFormatting($value, $result);
                $value = $mapping[$value];
            }
            
            $this->rules[] = array($value, $result);
        }    
    }
    
    private function handleStringFormatting($value, $result)
    {
        if($value === '<string>')
        {
            $result = function ($value) use ($result) {
                return str_replace('<string>', $value, $result);
            };
        }
        
        return $result;
    }
    
    public function format($value)
    {
        foreach($this->rules as $rule)
        {
            list($condition, $result) = $rule;
            
            if($this->isRuleMatches($condition, $value))
            {
                return $this->applyFormattingRule($result, $value);
            }
        }
        
        return $value;
    }
    
    private function isRuleMatches($condition, $value)
    {
        $hasMatched = ($condition === $value);
        
        if($condition instanceof \Closure)
        {
            $hasMatched = $condition($value);
        }    
        
        return $hasMatched;
    }
    
    private function applyFormattingRule($ruleResult, $value)
    {
        if($ruleResult instanceof \Closure)
        {
            return $ruleResult($value);
        }
        
        return $ruleResult;
    }
}
