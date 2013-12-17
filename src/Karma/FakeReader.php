<?php

namespace Karma;

class FakeReader implements Configuration
{
    private
        $values;
    
    public function __construct(array $values = array())
    {
        $this->values = $values;
    }
    
    public function read($variable, $environment)
    {
        $key = "$variable:$environment";
        
        if(array_key_exists($key, $this->values))
        {
            return $this->values[$key];
        }
        
        return null;
    }
}