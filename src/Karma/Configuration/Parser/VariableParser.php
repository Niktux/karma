<?php

namespace Karma\Configuration\Parser;

class VariableParser extends AbstractGroupParser
{
    const 
        ASSIGNMENT = '=',
        ENV_SEPARATOR = ',',
        DEFAULT_ENV = 'default';
    
    private
        $currentVariable,
        $variables;
    
    public function __construct()
    {
        $this->currentVariable = null;
        $this->variables = array();
    }
    
    public function parse($line)
    {
        $variableName = $this->extractVariableName($line); 
        
        if($variableName !== null)
        {
            return $this->changeCurrentVariable($variableName);
        }

        $this->parseEnvironmentValue($line);
    }    
    
    private function extractVariableName($line)
    {
        $variableName = null;
        
        if(preg_match('~(?P<variableName>[^:]+):$~', $line, $matches))
        {
            $variableName = trim($matches['variableName']);
        }
        
        return $variableName;
    }
    
    private function changeCurrentVariable($variableName)
    {
        $this->currentVariable = $variableName;

        if(isset($this->variables[$this->currentVariable]))
        {
            throw new \RuntimeException(sprintf(
                'Variable %s is already declared in %s',
                $this->currentVariable,
                $this->variables[$this->currentVariable]['file']
            ));
        }
        
        $this->variables[$this->currentVariable] = array(
            'env' => array(),
            'file' => $this->currentFilePath,
        );
    }
    
    private function parseEnvironmentValue($line)
    {
        if($this->currentVariable === null)
        {
            throw new \RuntimeException(sprintf(
                'Missing variable name in file %s',
                $this->currentFilePath
            ));
        }
        
        if(substr_count($line, self::ASSIGNMENT) < 1)
        {
            throw new \RuntimeException(sprintf(
                'Syntax error in %s : line must contains = (%s)',
                $this->currentFilePath,
                $line
            ));
        }
        
        list($envList, $value) = explode(self::ASSIGNMENT, $line, 2);
        $environments = array_map('trim', explode(self::ENV_SEPARATOR, $envList));
        
        $value = $this->filterValue($value);
        
        foreach($environments as $environment)
        {
            if(array_key_exists($environment, $this->variables[$this->currentVariable]['env']))
            {
                throw new \RuntimeException(sprintf(
                    'Duplicated value for environment %s and variable %s',
                    $environment,
                    $this->currentVariable            
                ));
            } 
            
            $this->variables[$this->currentVariable]['env'][$environment] = $value;
        }
    }
    
    private function filterValue($value)
    {
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
    
    public function getVariables()
    {
        return $this->variables;
    }
    
    public function setCurrentFile($filePath)
    {
        parent::setCurrentFile($filePath);
        
        $this->currentVariable = null;
    }
}