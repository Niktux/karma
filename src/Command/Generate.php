<?php

declare(strict_types = 1);

namespace Karma\Command;

use Karma\ConfigurableProcessor;
use Karma\Application;

class Generate extends ConfigureActionCommand
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

    protected function getProcessor(): ConfigurableProcessor
    {
        return $this->app['configurationFilesGenerator'];
    }

    protected function launchConfigurationAction(ConfigurableProcessor $processor): void
    {
        $processor->generate($this->environment);
    }
}
