<?php

namespace Karma\Command;

use Karma\ConfigurableProcessor;
use Karma\Application;

class Hydrate extends ConfigureActionCommand
{
    public function __construct(Application $app)
    {
        parent::__construct(
            $app,
            'hydrate',
            'Hydrate dist files',
            'Hydrate'
        );
    }

    protected function getProcessor()
    {
        return $this->app['hydrator'];
    }

    protected function launchConfigurationAction(ConfigurableProcessor $processor)
    {
        $processor->hydrate($this->environment);
    }
}
