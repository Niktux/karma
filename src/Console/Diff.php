<?php

declare(strict_types = 1);

namespace Karma\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Karma\Display\CliTable;
use Karma\Console;

class Diff extends Command
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('diff')
            ->setDescription('Display differences between environment variable set')

            ->addArgument('env1', InputArgument::REQUIRED, 'First environment to compare')
            ->addArgument('env2', InputArgument::REQUIRED, 'Second environment to compare')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $environment1 = $input->getArgument('env1');
        $environment2 = $input->getArgument('env2');

        $output->writeln(sprintf(
            "<info>Diff between <comment>%s</comment> and <comment>%s</comment></info>\n",
            $environment1,
            $environment2
        ));

        $diff = $this->app['configuration']->compareEnvironments($environment1, $environment2);

        $output->writeln('');

        $table = new CliTable($diff);

        $table->enableFormattingTags()
            ->setHeaders(array($environment1, $environment2))
            ->displayKeys()
            ->setValueRenderingFunction(function($value){
                return $this->formatValue($value);
            });

        $output->writeln($table->render());
    }
}
