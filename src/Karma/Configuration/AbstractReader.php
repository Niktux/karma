<?php

namespace Karma\Configuration;

use Karma\Configuration;

abstract class AbstractReader implements Configuration
{
    protected
        $defaultEnvironment;

    private
        $overridenVariables,
        $customData;

    public function __construct()
    {
        $this->defaultEnvironment = 'dev';
        $this->overridenVariables = array();
        $this->customData = array();
    }

    public function read($variable, $environment = null)
    {
        $value = null;

        if(array_key_exists($variable, $this->overridenVariables))
        {
            $value = $this->overridenVariables[$variable];
        }
        else
        {
            $value = $this->readRaw($variable, $environment);
        }

        return $this->handleCustomData($value);
    }

    abstract protected function readRaw($variable, $environment = null);

    public function setDefaultEnvironment($environment)
    {
        if(! empty($environment) && is_string($environment))
        {
            $this->defaultEnvironment = $environment;
        }

        return $this;
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

    public function overrideVariable($variable, $value)
    {
        $this->overridenVariables[$variable] = $value;

        return $this;
    }

    public function setCustomData($customDataName, $value)
    {
        $key = '${' . $customDataName . '}';
        $this->customData[$key] = $value;

        return $this;
    }

    private function handleCustomData($value)
    {
        if(is_array($value))
        {
            return array_map(function($value) {
                return $this->handleCustomData($value);
            }, $value);
        }

        if(! is_string($value))
        {
            return $value;
        }

        return strtr($value, $this->customData);
    }
}
