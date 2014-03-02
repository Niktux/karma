<?php

namespace Karma;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Karma\Logging\OutputInterfaceAdapter;

class Command extends \Symfony\Component\Console\Command\Command
{
    use \Karma\Logging\OutputAware;
    
    protected
        $app;
    
    public function __construct(Application $app)
    {
        parent::__construct();
        
        $this->app = $app;
    }
    
    protected function configure()
    {
        $this
            ->addOption('confDir', null, InputOption::VALUE_REQUIRED, 'Configuration root directory', null)
            ->addOption('master', null, InputOption::VALUE_REQUIRED, 'Configuration master file', null)
            ->addOption('cache', null, InputOption::VALUE_NONE, 'Cache the dist files list')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setOutput($output);

        $profile = $this->app['profile'];
        
        $confDir = $input->getOption('confDir');
        if($confDir === null)
        {
            $confDir = Application::DEFAULT_CONF_DIRECTORY;
            if($profile->hasConfigurationDirectory())
            {
                $confDir = $profile->getConfigurationDirectory();
            }
        } 
        
        $masterFile = $input->getOption('master');
        if($masterFile === null)
        {
            $masterFile = Application::DEFAULT_MASTER_FILE;
            if($profile->hasMasterFilename())
            {
                $masterFile = $profile->getMasterFilename();
            }
        } 
        
        $this->app['configuration.path']       = $confDir;
        $this->app['configuration.masterFile'] = $masterFile;
        
        $this->app['logger'] = $this->app->factory(function($c) use($output) {
            return new OutputInterfaceAdapter($output);
        });
        
        if($input->getOption('cache'))
        {
            $this->enableFinderCache();
        }
    }
    
    private function enableFinderCache()
    {
        $this->app['sources.fileSystem.finder'] = $this->app->factory(function ($c) {
            return $c['sources.fileSystem.cached'];
        });
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