<?php

namespace Karma;

class InMemoryReader implements Configuration
{
    private
        $defaultEnvironment,
        $values;
    
    public function __construct(array $values = array())
    {
        $this->defaultEnvironment = 'dev';
        $this->values = $values;
    }
    
    public function read($variable, $environment = null)
    {
        if($environment === null)
        {
            $environment = $this->defaultEnvironment;
        }
        
        $key = "$variable:$environment";
        
        if(array_key_exists($key, $this->values))
        {
            return $this->values[$key];
        }
        
        return null;
    }
    
    public function setDefaultEnvironment($environment)
    {
        $this->defaultEnvironment = $environment;
        
        return $this; 
    }
}