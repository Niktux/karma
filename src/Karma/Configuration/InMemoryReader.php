<?php

namespace Karma\Configuration;

class InMemoryReader extends AbstractReader
{
    private
        $values;
    
    public function __construct(array $values = array())
    {
        parent::__construct();
        
        $this->values = $values;
    }
    
    protected function readRaw($variable, $environment = null)
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
        
        throw new \RuntimeException("Variable $variable does not exist");
    }
    
    public function getAllVariables()
    {
        $variables = array_map(function($item){
            return explode(':', $item)[0];
        }, array_keys($this->values));
        
        return array_unique($variables);
    }
}