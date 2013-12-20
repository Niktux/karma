<?php

namespace Karma\Configuration;

trait FilterInputVariable
{
    private function filterValue($value)
    {
        if(! is_string($value))
        {
            return $value;
        }
        
        $value = trim($value);
    
        $knowValues = array(
            'true' => true,
            'false' => false,
            'null' => null
        );
    
        if(array_key_exists(strtolower($value), $knowValues))
        {
            return $knowValues[strtolower($value)];
        }
    
        if(is_numeric($value))
        {
            if(stripos($value, '.') !== false && floatval($value) == $value)
            {
                return floatval($value);
            }
    
            return intval($value);
        }
    
        return $value;
    }    
}