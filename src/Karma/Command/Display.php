<?php

namespace Karma\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Gaufrette\Filesystem;
use Gaufrette\Adapter\Local;
use Karma\Finder;
use Karma\Hydrator;
use Karma\Configuration\InMemoryReader;
use Karma\Application;
use Karma\Configuration;

class Display extends Command
{
    use \Karma\Logging\OutputAware;
    
    const
        ENV_DEV = 'dev';
    
    protected function configure()
    {
        $this
            ->setName('display')
            ->setDescription('Display environment variable set')
            
            ->addOption('env',     null, InputOption::VALUE_REQUIRED, 'Target environment',           self::ENV_DEV)
            ->addOption('confDir', null, InputOption::VALUE_REQUIRED, 'Configuration root directory', Application::DEFAULT_CONF_DIRECTORY)
            ->addOption('master',  null, InputOption::VALUE_REQUIRED, 'Configuration master file',    Application::DEFAULT_MASTER_FILE)
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setOutput($output);
        
        $environment = $input->getOption('env'); 
        
        $this->output->writeln(sprintf(
            '<info>Display <comment>%s</comment> values</info>',
            $environment
        ));
        
        $app = new \Karma\Application();
        $app['configuration.path']       = $input->getOption('confDir');
        $app['configuration.masterFile'] = $input->getOption('master');
        
        $reader = $app['configuration'];
        $reader->setDefaultEnvironment($input->getOption('env'));
        
        $this->displayValues($reader);
    }
    
    private function displayValues(Configuration $reader)
    {
        $variables = $reader->getAllVariables();
        sort($variables);
        
        foreach($variables as $variable)
        {
            $value = '<error>NOT FOUND</error>';
            try
            {
                $value = $reader->read($variable);
            }
            catch(\RuntimeException $e)
            {
            }
            
            $this->output->writeln(sprintf(
	           '<fg=cyan>%s</fg=cyan> = %s',
                $variable,
                $value
            ));
        }
    }
}