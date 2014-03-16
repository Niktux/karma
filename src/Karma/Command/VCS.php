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
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);
        
        $this->output->writeln("<info>Looking for vcs operations</info>\n");
        
        $this->app['sources.path'] = $input->getArgument('sourcePath');
        
        $vcs = $this->app['vcsHandler']($this->app['vcs']);
        $vcs->execute($this->app['sources.path']);
    }
}