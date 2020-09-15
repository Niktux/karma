<?php

declare(strict_types = 1);

namespace Karma\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Karma\Configuration;
use Karma\Command;
use Karma\Configuration\ValueFilterIterator;

class Display extends Command
{
    private const
        ENV_DEV = 'dev',
        NO_FILTERING = 'karma-nofiltering';

    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('display')
            ->setDescription('Display environment variable set')

            ->addOption('env', 'e', InputOption::VALUE_REQUIRED, 'Target environment', self::ENV_DEV)
            ->addOption('value', 'f', InputOption::VALUE_REQUIRED, 'Display only variable with this value', self::NO_FILTERING)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $environment = $input->getOption('env');

        $this->output->writeln(sprintf(
            "<info>Display <comment>%s</comment> values</info>\n",
            $environment
        ));

        $reader = $this->app['configuration'];
        $reader->setDefaultEnvironment($input->getOption('env'));

        $this->displayValues($reader, $input->getOption('value'));

        return 0;
    }

    private function displayValues(Configuration $reader, $filter = self::NO_FILTERING): void
    {
        $values = new \ArrayIterator($reader->getAllValuesForEnvironment());

        if($filter !== self::NO_FILTERING)
        {
            $values = new ValueFilterIterator($filter, $values);
        }

        $this->output->writeln('');

        $values->ksort();

        foreach($values as $variable => $value)
        {
            $this->output->writeln(sprintf(
               '<fg=cyan>%s</fg=cyan> = %s',
                $variable,
                $this->formatValue($value)
            ));
        }
    }
}
