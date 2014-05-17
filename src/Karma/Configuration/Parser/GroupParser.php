<?php

namespace Karma\Configuration\Parser;

class GroupParser extends AbstractSectionParser
{
    private
        $groups;
    
    public function __construct()
    {
        $this->groups = array();
    }
    
    public function parse($line)
    {
        if($this->isACommentLine($line))
        {
            return true;
        }

        $line = trim($line);
        
        if(preg_match('~(?P<groupName>[^=])\s*=\s*\[(?P<envList>[^\[\]]*)\]~', $line, $matches))
        {
            return $this->processGroupDefinition($matches['groupName'], $matches['envList']);
        }
        
        throw new \RuntimeException(sprintf(
        	'Syntax error on line %d in %s : %s',
            -1, // FIXME
            $this->currentFilePath,
            $line
        ));
    }
    
    private function processGroupDefinition($groupName, $envList)
    {
        
    }
    
    public function getCollectedGroups()
    {
        return $this->groups;
    }
}