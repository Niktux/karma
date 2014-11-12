<?php

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

    protected function getProcessor()
    {
        return $this->app['configurationFilesGenerator'];
    }

    protected function launchConfigurationAction(ConfigurableProcessor $processor)
    {
        $processor->generate($this->environment);
    }
}
