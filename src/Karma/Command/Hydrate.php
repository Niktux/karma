<?php

namespace Karma\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Karma\Application;
use Karma\Logging\OutputInterfaceAdapter;
use Karma\Command;

class Hydrate extends Command
{
    const
        ENV_DEV = 'dev';
    
    protected function configure()
    {
        parent::configure();
        
        $this
            ->setName('hydrate')
            ->setDescription('Hydrate dist files')
            
            ->addArgument('sourcePath', InputArgument::REQUIRED, 'source path to hydrate')
            
            ->addOption('env',     null, InputOption::VALUE_REQUIRED, 'Target environment', self::ENV_DEV)
            ->addOption('suffix',  null, InputOption::VALUE_REQUIRED, 'File suffix',        Application::DEFAULT_DISTFILE_SUFFIX)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simulation mode')
            ->addOption('backup',  null, InputOption::VALUE_NONE, 'Backup overwritten files')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);
        
        $environment = $input->getOption('env'); 
        
        $this->output->writeln(sprintf(
            '<info>Hydrate <comment>%s</comment> with <comment>%s</comment> values</info>',
            $input->getArgument('sourcePath'),
            $environment
        ));
        
        $this->app['sources.path']     = $input->getArgument('sourcePath');
        $this->app['distFiles.suffix'] = $input->getOption('suffix');
        
        $hydrator = $this->app['hydrator'];
        $hydrator->setLogger(new OutputInterfaceAdapter($output));
        
        if($input->getOption('dry-run'))
        {
            $this->output->writeln("<fg=cyan>*** Run in dry-run mode ***</fg=cyan>");
            $hydrator->setDryRun();
        }
        
        if($input->getOption('backup'))
        {
            $this->output->writeln("<fg=cyan>Backup enabled</fg=cyan>");
            $hydrator->enableBackup();
        }
            
        $hydrator->hydrate($environment);
    }
}