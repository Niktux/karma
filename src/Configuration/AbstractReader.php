<?php

declare(strict_types = 1);

namespace Karma\Configuration;

use Karma\Configuration;

abstract class AbstractReader implements Configuration
{
    private array
        $overriddenVariables,
        $customData;

    protected string
        $defaultEnvironment;

    public function __construct()
    {
        $this->defaultEnvironment = 'dev';
        $this->overriddenVariables = [];
        $this->customData = [];
    }

    public function read(string $variable, ?string $environment = null): mixed
    {
        $value = null;

        if(array_key_exists($variable, $this->overriddenVariables))
        {
            $value = $this->overriddenVariables[$variable];
        }
        else
        {
            $value = $this->readRaw($variable, $environment);
        }

        return $this->handleCustomData($value);
    }

    abstract protected function readRaw(string $variable, ?string $environment = null): mixed;

    public function setDefaultEnvironment(string $environment): void
    {
        if(! empty($environment))
        {
            $this->defaultEnvironment = $environment;
        }
    }

    public function allValuesForEnvironment(?string $environment = null): array
    {
        $result = [];

        $variables = $this->allVariables();

        foreach($variables as $variable)
        {
            try
            {
                $value = $this->read($variable, $environment);
            }
            catch(\RuntimeException)
            {
                $value = Configuration::NOT_FOUND;
            }

            $result[$variable] = $value;
        }

        return $result;
    }

    public function overrideVariable(string $variable, $value): void
    {
        $this->overriddenVariables[$variable] = $value;
    }

    public function setCustomData(string $customDataName, $value): void
    {
        $key = '${' . $customDataName . '}';
        $this->customData[$key] = $value;
    }

    private function handleCustomData(mixed $value): mixed
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
