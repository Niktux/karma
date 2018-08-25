<?php

declare(strict_types = 1);

namespace Karma\Console;

use Karma\ConfigurableProcessor;
use Karma\Application;
use Karma\Hydrator;

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

    protected function getProcessor(): ConfigurableProcessor
    {
        return $this->app['hydrator'];
    }

    protected function launchConfigurationAction(ConfigurableProcessor $processor): void
    {
        $processor->hydrate($this->environment);
        $this->warnForUnusedVariables($processor);
        $this->warnForUnvaluedVariables($processor);
    //    $this->hydrationStats($processor);
    }

    private function warnForUnusedVariables(Hydrator $processor)
    {
        $unusedVariables = $processor->getUnusedVariables();

        if(! empty($unusedVariables))
        {
            $logger = $this->app['logger'];

            $logger->warning('You have unused variables : you should remove them or check if you have not mispelled them');

            $logger->warning(sprintf(
                'Unused variables : %s',
                implode(', ', $unusedVariables)
            ));
        }
    }

    private function warnForUnvaluedVariables(Hydrator $processor): void
    {
        $unvaluedVariables = $processor->getUnvaluedVariables();

        if(! empty($unvaluedVariables))
        {
            $logger = $this->app['logger'];

            $logger->warning(sprintf(
                'Missing values for variables : %s (TODO markers found)',
                implode(', ', $unvaluedVariables)
            ));
        }
    }

    private function hydrationStats(Hydrator $processor): void
    {
        foreach($processor->hydratedFiles() as $file => $nbVariables)
        {
            $logger = $this->app['logger'];

            $logger->debug(sprintf(
                '%-30s [%2d]',
                $file,
                $nbVariables
            ));
        }
    }
}
