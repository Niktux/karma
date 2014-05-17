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
        $currentLineNumber,
        $variables,
        $valueFound;
    
    public function __construct()
    {
        $this->currentVariable = null;
        $this->currentLineNumber = -1;
        $this->variables = array();
        $this->valueFound = false;
    }
    
    public function parse($line, $lineNumber)
    {
        if($this->isACommentLine($line))
        {
            return true;
        }
        
        $this->currentLineNumber = $lineNumber;
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
                'Variable %s has no value (declared in file %s line %d)',
                $this->currentVariable,
                $this->variables[$this->currentVariable]['file'],
                $this->currentLineNumber
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
            throw new \RuntimeException(sprintf(
                'Missing variable name in file %s line %d',
                $this->currentFilePath,
                $this->currentLineNumber
            ));
        }
        
        if(substr_count($line, self::ASSIGNMENT) < 1)
        {
            throw new \RuntimeException(sprintf(
                'Syntax error in %s line %d : line must contains = (%s)',
                $this->currentFilePath,
                $this->currentLineNumber,
                $line
            ));
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
                throw new \RuntimeException(sprintf(
                    'Duplicated value for environment %s and variable %s (in %s line %d)',
                    $environment,
                    $this->currentVariable,
                    $this->currentFilePath,
                    $this->currentLineNumber
                ));
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