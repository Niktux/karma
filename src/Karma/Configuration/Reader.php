<?php

namespace Karma\Configuration;

use Karma\Configuration;

class Reader implements Configuration
{
    const
        DEFAULT_ENVIRONMENT = 'default',
        DEFAULT_VALUE_FOR_ENVIRONMENT_PARAMETER = 'prod',
        EXTERNAL = '<external>';
    
    private
        $defaultEnvironment,
        $variables,
        $externalReader;
    
    public function __construct(array $variables, array $externalVariables)
    {
        $this->defaultEnvironment = self::DEFAULT_VALUE_FOR_ENVIRONMENT_PARAMETER;
        
        $this->variables = $variables;
        
        $this->externalReader = null;
        if(! empty($externalVariables))
        {
            $this->externalReader = new Reader($externalVariables, array());
        }
    }    
    
    public function setDefaultEnvironment($environment)
    {
        if(! empty($environment) && is_string($environment))
        {
            $this->defaultEnvironment = $environment;
        }
        
        return $this;
    }
    
    public function read($variable, $environment = null)
    {
        if($environment === null)
        {
            $environment = $this->defaultEnvironment;
        }
        
        return $this->readVariable($variable, $environment);
    }
    
    private function readVariable($variable, $environment)
    {
        if(! array_key_exists($variable, $this->variables))
        {
            throw new \RuntimeException(sprintf(
                'Unknown variable %s',
                $variable
            ));   
        }
        
        $envs = $this->variables[$variable]['env'];

        foreach(array($environment, self::DEFAULT_ENVIRONMENT) as $searchedEnvironment)
        {
            if(array_key_exists($searchedEnvironment, $envs))
            {
                $value = $envs[$searchedEnvironment];
                
                if($value === self::EXTERNAL)
                {
                    $value = $this->processExternal($variable, $environment);
                }
                
                return $value;
            }
        }
        
        throw new \RuntimeException(sprintf(
            'Value not found of variable %s in environment %s (and no default value has been provided)',
            $variable,
            $environment
        ));
    }
    
    private function processExternal($variable, $environment)
    {
        if(! $this->externalReader instanceof Reader)
        {
            throw new \RuntimeException(sprintf(
                'There is no external variables. %s can not be resolve for environment %s',
                $variable,
                $environment
            ));    
        }
        
        return $this->externalReader->read($variable, $environment);
    }
    
    public function getAllVariables()
    {
        return array_keys($this->variables);
    }
    
    public function getAllValuesForEnvironment($environment = null)
    {
        $result = array();
        
        $variables = $this->getAllVariables();
        
        foreach($variables as $variable)
        {
            try
            {
                $value = $this->read($variable, $environment);
            }
            catch(\RuntimeException $e)
            {
                $value = Configuration::NOT_FOUND;
            }
        
            $result[$variable] = $value;
        }    
        
        return $result;
    }
    
    public function compareEnvironments($environment1, $environment2)
    {
        $values1 = $this->getAllValuesForEnvironment($environment1);
        $values2 = $this->getAllValuesForEnvironment($environment2);
        
        $diff = array();
        
        foreach($values1 as $name => $value1)
        {
            $value2 = $values2[$name];
            
            if($value1 !== $value2)
            {
                $diff[$name] = array($value1, $value2);
            }
        }
        
        return $diff;
    }
    
    public function overrideVariable($variable, $value)
    {
        
    }
}