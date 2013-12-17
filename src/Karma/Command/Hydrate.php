<?php

namespace Karma\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Hydrate extends Command
{
    protected function configure()
    {
        $this->setName('hydrate')
            ->setDescription('Hydrate dist files')
            ->addArgument('env', InputArgument::REQUIRED, 'target environment');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $text = 'Hydrate environment ' . $input->getArgument('env');
        
        $output->writeln($text);
    }
}