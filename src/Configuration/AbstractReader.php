<?php

declare(strict_types = 1);

namespace Karma\Configuration;

use Karma\Configuration;

abstract class AbstractReader implements Configuration
{
    private array
        $overridenVariables,
        $customData;

    protected string
        $defaultEnvironment;

    public function __construct()
    {
        $this->defaultEnvironment = 'dev';
        $this->overridenVariables = [];
        $this->customData = [];
    }

    public function read(string $variable, ?string $environment = null)
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

    abstract protected function readRaw(string $variable, ?string $environment = null);

    public function setDefaultEnvironment(string $environment): void
    {
        if(! empty($environment) && is_string($environment))
        {
            $this->defaultEnvironment = $environment;
        }
    }

    public function getAllValuesForEnvironment(?string $environment = null): array
    {
        $result = [];

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

    public function overrideVariable(string $variable, $value): void
    {
        $this->overridenVariables[$variable] = $value;
    }

    public function setCustomData(string $customDataName, $value): void
    {
        $key = '${' . $customDataName . '}';
        $this->customData[$key] = $value;
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
