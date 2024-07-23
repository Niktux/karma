<?php

declare(strict_types = 1);

namespace Karma\Console;

use Karma\ConfigurableProcessor;
use Karma\Application;
use Karma\Generator\ConfigurationFileGenerator;

final class Generate extends ConfigureActionCommand
{
    public function __construct(Application $app)
    {
        parent::__construct(
            $app,
            'generate',
            'Generate configuration files for given environment',
            'Generate configuration files in'
        );
    }

    protected function processor(): ConfigurableProcessor
    {
        return $this->app['configurationFilesGenerator'];
    }

    protected function launchConfigurationAction(ConfigurableProcessor $processor): void
    {
        if($processor instanceof ConfigurationFileGenerator)
        {
            $processor->generate($this->environment);
        }
    }
}
