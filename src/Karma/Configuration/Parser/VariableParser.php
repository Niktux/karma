<?php

namespace Karma\Configuration\Parser;

class VariableParser extends AbstractGroupParser
{
    use \Karma\Configuration\FilterInputVariable;
    
    const 
        ASSIGNMENT = '=',
        ENV_SEPARATOR = ',',
        DEFAULT_ENV = 'default';
    
    private
        $currentVariable,
        $variables,
        $valueFound;
    
    public function __construct()
    {
        $this->currentVariable = null;
        $this->variables = array();
        $this->valueFound = false;
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
    
    private function checkCurrentVariableState()
    {
        if($this->currentVariable !== null && $this->valueFound === false)
        {
            throw new \RuntimeException(sprintf(
                'Variable %s has no value (declared in file %s)',
                $this->currentVariable,
                $this->variables[$this->currentVariable]['file']
            ));
        }
    }
        
    private function changeCurrentVariable($variableName)
    {
        $this->checkCurrentVariableState();
        $this->currentVariable = $variableName;

        if(isset($this->variables[$this->currentVariable]))
        {
            throw new \RuntimeException(sprintf(
                'Variable %s is already declared in %s (raised from %s)',
                $this->currentVariable,
                $this->variables[$this->currentVariable]['file'],
                $this->currentFilePath
            ));
        }
        
        $this->variables[$this->currentVariable] = array(
            'env' => array(),
            'file' => $this->currentFilePath,
        );
        
        $this->valueFound = false;
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
        
        $this->valueFound = true;
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
    
    public function endOfFileCheck()
    {
        $this->checkCurrentVariableState();
    }
}