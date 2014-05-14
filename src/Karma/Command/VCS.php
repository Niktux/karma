<?php

namespace Karma\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Karma\Command;
use Symfony\Component\Console\Input\InputArgument;

class VCS extends Command
{
    protected function configure()
    {
        parent::configure();
        
        $this
            ->setName('vcs')
            ->setDescription('')
            ->addArgument('sourcePath', InputArgument::OPTIONAL, 'source path')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);
        
        $this->output->writeln("<info>Looking for vcs operations</info>\n");
        
        $sourcePath = $input->getArgument('sourcePath');
        if($sourcePath === null)
        {
            $profile = $this->app['profile'];
            if($profile->hasSourcePath() !== true)
            {
                throw new \RuntimeException('Missing argument sourcePath');
            }
        
            $sourcePath = $profile->getSourcePath();
        }
        
        $this->app['sources.path'] = $sourcePath;
        
        $vcs = $this->app['vcsHandler']($this->app['vcs']);
        $vcs->execute($this->app['sources.path']);
    }
}