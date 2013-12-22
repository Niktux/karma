<?php

namespace Karma\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Karma\Configuration;
use Karma\Configuration\ValueFilter;
use Karma\Command;

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
            
            ->addOption('env', null, InputOption::VALUE_REQUIRED, 'Target environment', self::ENV_DEV)
            ->addOption('value', null, InputOption::VALUE_REQUIRED, 'Display only variable with this value', self::NO_FILTERING)
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);
        
        $environment = $input->getOption('env'); 
        
        $this->output->writeln(sprintf(
            '<info>Display <comment>%s</comment> values</info>',
            $environment
        ));
        
        $reader = $this->app['configuration'];
        $reader->setDefaultEnvironment($input->getOption('env'));
        
        $this->displayValues($reader, $input->getOption('value'));
    }
    
    private function displayValues(Configuration $reader, $filter = self::NO_FILTERING)
    {
        $values = $reader->getAllValuesForEnvironment();
        
        if($filter !== self::NO_FILTERING)
        {
            $valueFilter = new ValueFilter($values);
            $values = $valueFilter->filter($filter);    
        }
        
        $variables = array_keys($values);
        sort($variables);
        
        foreach($variables as $variable)
        {
            $this->output->writeln(sprintf(
               '<fg=cyan>%s</fg=cyan> = %s',
                $variable,
                $this->formatValue($values[$variable])
            ));
        }
    }
}