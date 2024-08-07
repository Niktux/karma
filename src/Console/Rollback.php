<?php

declare(strict_types = 1);

namespace Karma\Console;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Karma\Application;
use Karma\Command;

final class Rollback extends Command
{
    private bool
        $dryRun;

    public function __construct(Application $app)
    {
        parent::__construct($app);

        $this->dryRun = false;
    }

    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('rollback')
            ->setDescription('Restore files from backup ones')

            ->addArgument('sourcePath', InputArgument::OPTIONAL, 'source path to hydrate')

            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simulation mode')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $this->processInputs($input);
        $this->launchRollback();

        return 0;
    }

    private function processInputs(InputInterface $input): void
    {
        $sourcePath = $input->getArgument('sourcePath');
        if($sourcePath === null)
        {
            $profile = $this->app['profile'];
            if($profile->hasSourcePath() !== true)
            {
                throw new \RuntimeException('Missing argument sourcePath');
            }

            $sourcePath = $profile->sourcePath();
        }

        $this->output->writeln(sprintf(
            '<info>Rollback <comment>%s</comment></info>',
            $sourcePath
        ));

        $this->app['sources.path'] = $sourcePath;

        if($input->getOption('dry-run'))
        {
            $this->output->writeln("<fg=cyan>Run in dry-run mode</fg=cyan>");
            $this->dryRun = true;
        }

        $this->output->writeln('');
    }

    private function launchRollback(): void
    {
        $hydrator = $this->app['hydrator'];

        if($this->dryRun === true)
        {
            $hydrator->setDryRun();
        }

        $hydrator->rollback();
    }
}
