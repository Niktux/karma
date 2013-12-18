<?php

namespace Karma\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Gaufrette\Filesystem;
use Gaufrette\Adapter\Local;
use Karma\Finder;
use Karma\Hydrator;
use Karma\InMemoryReader;

class Hydrate extends Command
{
    use \Karma\Logging\OutputAware;
    
    const
        ENV_DEV = 'dev',
        DEFAULT_FILE_SUFFIX = '-dist';
    
    protected function configure()
    {
        $this
            ->setName('hydrate')
            ->setDescription('Hydrate dist files')
            
            ->addArgument('sourcePath', InputArgument::REQUIRED, 'source path to hydrate')
            
            ->addOption('env',    null, InputOption::VALUE_REQUIRED, 'Target environment', self::ENV_DEV)
            ->addOption('suffix', null, InputOption::VALUE_REQUIRED, 'File suffix',        self::DEFAULT_FILE_SUFFIX)
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setOutput($output);
        
        $environment = $input->getOption('env'); 
        
        $this->output->writeln(sprintf(
            '<info>Hydrate <comment>%s</comment> with <comment>%s</comment> values</info>',
            $input->getArgument('sourcePath'),
            $environment
        ));
        
        $sourcePath = $input->getArgument('sourcePath');
        $fs = new Filesystem(new Local($sourcePath));
        
        // FIXME reader
        $reader = new InMemoryReader();
        
        $hydrater = new Hydrator($fs, $input->getOption('suffix'), $reader);
        $hydrater
            ->setOutput($output)
            ->hydrate($environment);
    }
}