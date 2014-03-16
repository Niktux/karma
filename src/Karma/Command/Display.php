<?php

namespace Karma\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Karma\Configuration;
use Karma\Command;
use Karma\Configuration\ValueFilterIterator;

class Display extends Command
{
    const
        ENV_DEV = 'dev',
        NO_FILTERING = 'karma-nofiltering';
    
    protected function configure()
    {
        parent::configure();
        
        $this
            ->setName('display')
            ->setDescription('Display environment variable set')
            
            ->addOption('env', 'e', InputOption::VALUE_REQUIRED, 'Target environment', self::ENV_DEV)
            ->addOption('value', 'f', InputOption::VALUE_REQUIRED, 'Display only variable with this value', self::NO_FILTERING)
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);
        
        $environment = $input->getOption('env'); 
        
        $this->output->writeln(sprintf(
            "<info>Display <comment>%s</comment> values</info>\n",
            $environment
        ));
        
        $reader = $this->app['configuration'];
        $reader->setDefaultEnvironment($input->getOption('env'));
        
        $this->displayValues($reader, $input->getOption('value'));
    }
    
    private function displayValues(Configuration $reader, $filter = self::NO_FILTERING)
    {
        $values = new \ArrayIterator($reader->getAllValuesForEnvironment());
        
        if($filter !== self::NO_FILTERING)
        {
            $values = new ValueFilterIterator($filter, $values);
        }
        
        $this->output->writeln('');
        
        $values->ksort();
        
        foreach($values as $variable => $value)
        {
            $this->output->writeln(sprintf(
               '<fg=cyan>%s</fg=cyan> = %s',
                $variable,
                $this->formatValue($value)
            ));
        }
    }
}