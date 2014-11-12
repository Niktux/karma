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
        $enableBackup;

    public function __construct(Filesystem $fs, Configuration $reader, VariableProvider $variableProvider)
    {
        $this->fs = $fs;
        $this->reader = $reader;
        $this->variableProvider = $variableProvider;
        $this->dryRun = false;
        $this->enableBackup = false;
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
            $value = $this->reader->read($variable, $environment);
            $this->generateVariable($translatedVariableName, $value);
        }

        $this->postGenerate();
    }

    abstract protected function generateVariable($variableName, $value);

    protected function preGenerate()
    {

    }

    protected function postGenerate()
    {

    }
}