<?php

namespace Karma\Generator\ConfigurationFileGenerators;

use Karma\Generator\ConfigurationFileGenerator;
use Gaufrette\Filesystem;
use Karma\Configuration;
use Karma\Generator\VariableProvider;

abstract class AbstractFileGenerator implements ConfigurationFileGenerator
{
    protected
        $fs,
        $reader,
        $variableProvider,
        $dryRun,
        $enableBackup,
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

    public function setDryRun($value = true)
    {
        $this->dryRun = (bool) $value;

        return $this;
    }

    public function enableBackup($value = true)
    {
        $this->enableBackup = (bool) $value;

        return $this;
    }

    public function generate($environment)
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

    public function setSystemEnvironment($environment)
    {
        $this->systemEnvironment = $environment;

        return $this;
    }

    abstract protected function generateVariable($variableName, $value);

    protected function preGenerate()
    {

    }

    protected function postGenerate()
    {

    }
}
