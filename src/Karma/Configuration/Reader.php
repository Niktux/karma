<?php

namespace Karma\Configuration;

class Reader extends AbstractReader
{
    const
        DEFAULT_ENVIRONMENT = 'default',
        DEFAULT_VALUE_FOR_ENVIRONMENT_PARAMETER = 'prod',
        EXTERNAL = '<external>';

    private
        $variables,
        $externalReader,
        $groupNames,
        $environmentGroups,
        $defaultEnvironmentsForGroups;

    public function __construct(array $variables, array $externalVariables, array $groups = array(), array $defaultEnvironmentsForGroups = array())
    {
        parent::__construct();

        $this->defaultEnvironment = self::DEFAULT_VALUE_FOR_ENVIRONMENT_PARAMETER;

        $this->variables = $variables;

        $this->externalReader = null;
        if(! empty($externalVariables))
        {
            $this->externalReader = new Reader($externalVariables, array(), $groups);
        }

        $this->loadGroups($groups);
        $this->defaultEnvironmentsForGroups = $defaultEnvironmentsForGroups;
    }

    private function loadGroups(array $groups)
    {
        $this->environmentGroups = array();

        foreach($groups as $group => $environments)
        {
            foreach($environments as $environment)
            {
                $this->environmentGroups[$environment] = $group;
            }
        }

        $this->groupNames = array_keys($groups);
    }

    protected function readRaw($variable, $environment = null)
    {
        if($environment === null)
        {
            $environment = $this->defaultEnvironment;
        }

        if(in_array($environment, $this->groupNames))
        {
            if(! isset($this->defaultEnvironmentsForGroups[$environment]))
            {
                throw new \RuntimeException(sprintf(
                   'Group can not be used as environment (try with group %s detected)',
                    $environment
                ));
            }

            $environment = $this->defaultEnvironmentsForGroups[$environment];
        }

        return $this->readVariable($variable, $environment);
    }

    private function readVariable($variableName, $environment)
    {
        $variable = $this->accessVariable($variableName);
        $envs = $variable['env'];

        foreach($this->getEnvironmentEntries($environment) as $entry)
        {
            if(array_key_exists($entry, $envs))
            {
                $value = $envs[$entry];

                if($value === self::EXTERNAL)
                {
                    $value = $this->processExternal($variableName, $environment);
                }

                return $value;
            }
        }

        throw new \RuntimeException(sprintf(
            'Value not found of variable %s in environment %s (and no default value has been provided)',
            $variableName,
            $environment
        ));
    }

    private function accessVariable($variableName)
    {
        if(! array_key_exists($variableName, $this->variables))
        {
            throw new \RuntimeException(sprintf(
                'Unknown variable %s',
                $variableName
            ));
        }

        return $this->variables[$variableName];
    }

    private function getEnvironmentEntries($environment)
    {
        $entries = array($environment);

        if(isset($this->environmentGroups[$environment]))
        {
            $entries[] = $this->environmentGroups[$environment];
        }

        $entries[] = self::DEFAULT_ENVIRONMENT;

        return $entries;
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

    public function isSystem($variableName)
    {
        $variable = $this->accessVariable($variableName);

        return $variable['system'];
    }
}
