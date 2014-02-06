<?php

namespace Karma\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Karma\Application;
use Karma\Command;

class Rollback extends Command
{
    protected function configure()
    {
        parent::configure();
        
        $this
            ->setName('rollback')
            ->setDescription('Restore files from backup ones')
            
            ->addArgument('sourcePath', InputArgument::REQUIRED, 'source path to hydrate')
            
            ->addOption('suffix', null, InputOption::VALUE_REQUIRED, 'File suffix', null)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simulation mode')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);
        
        $suffix = $input->getOption('suffix');
        if($suffix === null)
        {
            $suffix = Application::DEFAULT_DISTFILE_SUFFIX;
        
            $profile = $this->app['profile'];
            if($profile->hasTemplatesSuffix())
            {
                $suffix = $profile->getTemplatesSuffix();
            }
        }
        
        $this->output->writeln(sprintf(
            '<info>Rollback <comment>%s</comment></info>',
            $input->getArgument('sourcePath')
        ));
        
        $this->app['sources.path']     = $input->getArgument('sourcePath');
        $this->app['distFiles.suffix'] = $suffix;
        
        $hydrator = $this->app['hydrator'];
        
        if($input->getOption('dry-run'))
        {
            $this->output->writeln("<fg=cyan>*** Run in dry-run mode ***</fg=cyan>");
            $hydrator->setDryRun();
        }
        
        $hydrator->rollback();
    }
}