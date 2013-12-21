<?php

namespace Karma;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class Command extends \Symfony\Component\Console\Command\Command
{
    use \Karma\Logging\OutputAware;
    
    protected
        $app;
    
    protected function configure()
    {
        $this
            ->addOption('confDir', null, InputOption::VALUE_REQUIRED, 'Configuration root directory', Application::DEFAULT_CONF_DIRECTORY)
            ->addOption('master',  null, InputOption::VALUE_REQUIRED, 'Configuration master file',    Application::DEFAULT_MASTER_FILE)
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setOutput($output);
        
        $this->app = new Application();
        $this->app['configuration.path']       = $input->getOption('confDir');
        $this->app['configuration.masterFile'] = $input->getOption('master');
    }
    
    protected function formatValue($value)
    {
        if($value === false)
        {
            $value = 'false';
        }
        elseif($value === true)
        {
            $value = 'true';
        }
        elseif($value === null)
        {
            $value = '<fg=white;options=bold>NULL</fg=white;options=bold>';
        }
        elseif($value === Configuration::NOT_FOUND)
        {
            $value = '<error>NOT FOUND</error>';
        }
    
        return $value;
    }
}