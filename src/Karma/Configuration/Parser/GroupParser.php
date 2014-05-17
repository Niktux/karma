<?php

namespace Karma\Configuration\Parser;

class GroupParser extends AbstractSectionParser
{
    private
        $groups,
        $currentLineNumber;
    
    public function __construct()
    {
        $this->groups = array();
        $this->currentLineNumber = -1;
    }
    
    public function parse($line, $lineNumber)
    {
        if($this->isACommentLine($line))
        {
            return true;
        }

        $this->currentLineNumber = $lineNumber;
        $line = trim($line);
        
        if(preg_match('~(?P<groupName>[^=])\s*=\s*\[(?P<envList>[^\[\]]*)\]~', $line, $matches))
        {
            return $this->processGroupDefinition($matches['groupName'], $matches['envList']);
        }
        
        throw new \RuntimeException(sprintf(
        	'Syntax error in %s line %d : %s',
            $this->currentFilePath,
            $lineNumber,
            $line
        ));
    }
    
    private function processGroupDefinition($groupName, $envList)
    {
        $this->checkGroupStillNotExists($groupName);
        
        $environments = array_map('trim', explode(',', $envList));
        
        $this->groups[$groupName] = array();
        foreach($environments as $env)
        {
            if(empty($env))
            {
                throw new \RuntimeException(sprintf(
        	       'Syntax error in %s line %d : empty environment in declaration of group %s',
                    $this->currentFilePath,
                    $this->currentLineNumber,
                    $groupName
                ));
            }
            
            $this->groups[$groupName] = $env;
        }
    }
    
    private function checkGroupStillNotExists($groupName)
    {
        if(isset($this->groups[$groupName]))
        {
            throw new \RuntimeException(sprintf(
                'Syntax error in %s line %d : group %s has already been declared',
                $this->currentFilePath,
                $this->currentLineNumber,
                $groupName
            ));
        }
    }
    
    public function getCollectedGroups()
    {
        return $this->groups;
    }
}