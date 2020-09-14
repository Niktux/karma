<?php

declare(strict_types = 1);

namespace Karma\Generator\ConfigurationFileGenerators;

use Karma\ConfigurableProcessor;
use Karma\Generator\ConfigurationFileGenerator;
use Gaufrette\Filesystem;
use Karma\Configuration;
use Karma\Generator\VariableProvider;

abstract class AbstractFileGenerator implements ConfigurationFileGenerator
{
    protected Filesystem
        $fs;
    protected Configuration
        $reader;
    protected VariableProvider
        $variableProvider;
    protected bool
        $dryRun,
        $enableBackup;
    protected ?string
        $systemEnvironment;

    public function __construct(Filesystem $fs, Configuration $reader, VariableProvider $variableProvider)
    {
        $this->fs = $fs;
        $this->reader = $reader;
        $this->variableProvider = $variableProvider;
        $this->dryRun = false;
        $this->enableBackup = false;
        $this->systemEnvironment = null;
    }

    public function setDryRun(bool $value = true): ConfigurableProcessor
    {
        $this->dryRun = (bool) $value;

        return $this;
    }

    public function enableBackup(bool $value = true): ConfigurableProcessor
    {
        $this->enableBackup = (bool) $value;

        return $this;
    }

    public function generate(string $environment): void
    {
        $this->preGenerate();

        $variables = $this->variableProvider->getAllVariables();

        foreach($variables as $variable => $translatedVariableName)
        {
            $value = $this->read($variable, $environment);
            $this->generateVariable($translatedVariableName, $value);
        }

        $this->postGenerate();
    }

    private function read($variable, $environment)
    {
        if($this->systemEnvironment !== null && $this->reader->isSystem($variable))
        {
            $environment = $this->systemEnvironment;
        }

        return $this->reader->read($variable, $environment);
    }

    public function setSystemEnvironment(?string $environment): ConfigurableProcessor
    {
        $this->systemEnvironment = $environment;

        return $this;
    }

    abstract protected function generateVariable(string $variableName, $value): void;

    protected function preGenerate(): void
    {

    }

    protected function postGenerate(): void
    {

    }
}
