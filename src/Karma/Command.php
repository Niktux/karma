<?php

namespace Karma;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Karma\Logging\OutputInterfaceAdapter;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

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
        $this->addOption('cache', null, InputOption::VALUE_NONE, 'Cache the dist files list');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->configureOutputInterface($output);
        $this->printHeader();

        $profile = $this->app['profile'];
        
        $confDir = Application::DEFAULT_CONF_DIRECTORY;
        if($profile->hasConfigurationDirectory())
        {
            $confDir = $profile->getConfigurationDirectory();
        }
        
        $masterFile = Application::DEFAULT_MASTER_FILE;
        if($profile->hasMasterFilename())
        {
            $masterFile = $profile->getMasterFilename();
        }
        
        $suffix = Application::DEFAULT_DISTFILE_SUFFIX;
        if($profile->hasTemplatesSuffix())
        {
            $suffix = $profile->getTemplatesSuffix();
        }
        
        $this->app['configuration.path']       = $confDir;
        $this->app['configuration.masterFile'] = $masterFile;
        $this->app['distFiles.suffix'] = $suffix;
        
        $this->app['logger'] = new OutputInterfaceAdapter($output);
        
        if($input->getOption('cache'))
        {
            $this->enableFinderCache();
        }
    }
    
    private function configureOutputInterface(OutputInterface $output)
    {
        $style = new OutputFormatterStyle('cyan', null, array('bold'));
        $output->getFormatter()->setStyle('important', $style);
        
        $this->setOutput($output);
    }
    
    private function enableFinderCache()
    {
        $this->app['sources.fileSystem.finder'] = $this->app['sources.fileSystem.cached'];
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
        elseif(is_array($value))
        {
            array_walk($value, function($item) {
                return $this->formatValue($item);
            });
            
            $value = sprintf('[%s]', implode(', ', $value));
        }
    
        return $value;
    }
    
    private function printHeader()
    {
        $this->output->writeln(sprintf(
           '<comment>%s</comment>',
           $this->getLogo()
        ));
    }
    
    private function getLogo()
    {
        $logo = <<<ASCIIART
  _  __                          
 | |/ /__ _ _ __ _ __ ___   __ _ 
 | ' // _` | '__| '_ ` _ \ / _` |
 | . \ (_| | |  | | | | | | (_| |
 |_|\_\__,_|_|  |_| |_| |_|\__,_|

ASCIIART;
        
        return sprintf(
            "%s\n %s %s -\n",
            $logo,
            str_pad('', 30 - strlen(Application::VERSION), '-'),
            Application::VERSION
        );
        
    }
    
}