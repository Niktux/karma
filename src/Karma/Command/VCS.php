<?php

namespace Karma\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Karma\Command;
use GitWrapper\GitWrapper;

class VCS extends Command
{
    protected function configure()
    {
        parent::configure();
        
        $this
            ->setName('vcs')
            ->setDescription('')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);
        
        $wrapper = new GitWrapper();
        $git = $wrapper->workingCopy(getcwd());
        
        $output->writeln($git->getStatus());
    }
}