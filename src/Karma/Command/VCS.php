<?php

namespace Karma\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Karma\Command;
use Karma\Application;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class VCS extends Command
{
    protected function configure()
    {
        parent::configure();
        
        $this
            ->setName('vcs')
            ->setDescription('')
            ->addArgument('sourcePath', InputArgument::REQUIRED, 'source path')
            ->addOption('suffix', null, InputOption::VALUE_REQUIRED, 'File suffix', null)
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
        
        $this->output->writeln('Looking for vcs operations');
        
        $this->app['sources.path'] = $input->getArgument('sourcePath');
        $this->app['distFiles.suffix'] = $suffix;
        
        $vcs = $this->app['vcsHandler']($this->app['vcs']);
        $vcs->execute($this->app['sources.path']);
    }
}