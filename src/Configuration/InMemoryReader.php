<?php

declare(strict_types = 1);

namespace Karma\Configuration;

use Karma\Configuration;

final class InMemoryReader extends AbstractReader
{
    private array
        $values;

    public function __construct(array $values = [])
    {
        parent::__construct();

        $this->values = [];
        foreach($values as $key => $value)
        {
            $this->values[$this->removeSystemFlag($key)] = [
                'value' => $value,
                'system' => stripos($key, Configuration::SYSTEM_VARIABLE_FLAG) === 0,
            ];
        }
    }

    protected function readRaw(string $variable, ?string $environment = null): mixed
    {
        if($environment === null)
        {
            $environment = $this->defaultEnvironment;
        }

        $key = "$variable:$environment";

        if(array_key_exists($key, $this->values))
        {
            return $this->values[$key]['value'];
        }

        throw new \RuntimeException("Variable $variable does not exist");
    }

    public function allVariables(): array
    {
        $variables = array_map(function($key){
            return $this->extractVariableName($key);
        }, array_keys($this->values));

        return array_unique($variables);
    }

    public function isSystem(string $variableName): bool
    {
        foreach($this->values as $key => $variable)
        {
            if($this->extractVariableName($key) === $variableName)
            {
                return $variable['system'];
            }
        }

        return false;
    }

    private function extractVariableName(string $key): string
    {
        return explode(':', $key)[0];
    }

    private function removeSystemFlag(string $variableName): string
    {
        return ltrim($variableName, Configuration::SYSTEM_VARIABLE_FLAG);
    }
}
