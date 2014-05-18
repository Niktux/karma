<?php

namespace Karma\Configuration\Parser;

class VariableParser extends AbstractSectionParser
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
    
    protected function parseLine($line)
    {
        if($this->isACommentLine($line))
        {
            return true;
        }
        
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
            $this->triggerError(sprintf(
                'Variable %s has no value (declared in file %s line %d)',
                $this->currentVariable,
                $this->variables[$this->currentVariable]['file'],
                $this->variables[$this->currentVariable]['line']
            ));
        }
    }
        
    private function changeCurrentVariable($variableName)
    {
        $this->checkCurrentVariableState();
        $this->currentVariable = $variableName;

        if(isset($this->variables[$this->currentVariable]))
        {
            $this->triggerError(sprintf(
                'Variable %s is already declared (in %s line %d)',
                $this->currentVariable,
                $this->variables[$this->currentVariable]['file'],
                $this->variables[$this->currentVariable]['line']
            ));
        }
        
        $this->variables[$this->currentVariable] = array(
            'env' => array(),
            'file' => $this->currentFilePath,
            'line' => $this->currentLineNumber,
        );
        
        $this->valueFound = false;
    }
    
    private function parseEnvironmentValue($line)
    {
        if($this->currentVariable === null)
        {
            $this->triggerError('Missing variable name');
        }
        
        if(substr_count($line, self::ASSIGNMENT) < 1)
        {
            $this->triggerError("line must contains = ($line)");
        }
        
        list($envList, $value) = explode(self::ASSIGNMENT, $line, 2);
        $environments = array_map('trim', explode(self::ENV_SEPARATOR, $envList));
        
        $value = trim($value);
        $value = $this->parseList($value);
        
        $value = $this->filterValue($value);
        
        foreach($environments as $environment)
        {
            if(array_key_exists($environment, $this->variables[$this->currentVariable]['env']))
            {
                $this->triggerError("Duplicated value for environment $environment and variable $this->currentVariable");
            } 
            
            $this->variables[$this->currentVariable]['env'][$environment] = $value;
        }
        
        $this->valueFound = true;
    }
    
    private function parseList($value)
    {
        if(preg_match('~^\[(?P<valueList>[^\[\]]*)\]$~', $value, $matches))
        {
            $value = array_map('trim', explode(',', $matches['valueList']));
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
    
    public function endOfFileCheck()
    {
        $this->checkCurrentVariableState();
    }
}