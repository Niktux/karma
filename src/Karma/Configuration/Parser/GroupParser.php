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
    
    protected function parseLine($line)
    {
        if($this->isACommentLine($line))
        {
            return true;
        }

        $line = trim($line);
        
        if(preg_match('~(?P<groupName>[^=]+)\s*=\s*\[(?P<envList>[^\[\]]+)\]$~', $line, $matches))
        {
            return $this->processGroupDefinition($matches['groupName'], $matches['envList']);
        }
        
        $this->triggerError($line);
    }
    
    private function processGroupDefinition($groupName, $envList)
    {
        $groupName = trim($groupName);
        
        $this->checkGroupStillNotExists($groupName);
        
        $environments = array_map('trim', explode(',', $envList));
        $this->checkEnvironmentAreUnique($groupName, $environments);
        
        $this->groups[$groupName] = array();
        
        foreach($environments as $env)
        {
            if(empty($env))
            {
                $this->triggerError("empty environment in declaration of group $groupName");
            }
            
            $this->groups[$groupName][] = $env;
        }
    }
    
    private function checkGroupStillNotExists($groupName)
    {
        if(isset($this->groups[$groupName]))
        {
            $this->triggerError("group $groupName has already been declared");
        }
    }
    
    private function checkEnvironmentAreUnique($groupName, array $environments)
    {
        if($this->hasDuplicatedValues($environments))
        {
            $this->triggerError("duplicated environment in group $groupName");
        }
    }
    
    private function hasDuplicatedValues(array $values)
    {
        $duplicatedValues = array_filter(array_count_values($values), function ($counter) {
            return $counter !== 1;
        });
        
        return empty($duplicatedValues) === false;
    }
    
    public function getCollectedGroups()
    {
        return $this->groups;
    }
    
    public function postParse()
    {
        $this->checkEnvironmentsBelongToOnlyOneGroup();
        $this->checkGroupsAreNotPartsOfAnotherGroups();
    }
    
    private function checkEnvironmentsBelongToOnlyOneGroup()
    {
        $allEnvironments = $this->getAllEnvironmentsBelongingToGroups();

        if($this->hasDuplicatedValues($allEnvironments))
        {
            throw new \RuntimeException('Error : some environments are in various groups');
        }
    }
    
    private function getAllEnvironmentsBelongingToGroups()
    {
        $allEnvironments = array();
        
        foreach($this->groups as $group)
        {
            $allEnvironments = array_merge($allEnvironments, $group);
        }
        
        return $allEnvironments;
    }
    
    private function checkGroupsAreNotPartsOfAnotherGroups()
    {
        $allEnvironments = $this->getAllEnvironmentsBelongingToGroups();

        $errors = array_intersect($allEnvironments, array_keys($this->groups));
        
        if(! empty($errors))
        {
            throw new \RuntimeException(sprintf(
               'Error : a group can not be part of another group (%s)',
                implode(', ', $errors)
            ));
        }
    }
}