<?php

namespace Karma\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Karma\Application;
use Karma\Command;
use Karma\Configuration\FilterInputVariable;

class Hydrate extends Command
{
    use FilterInputVariable;
    
    const
        ENV_DEV = 'dev',
        OPTION_ASSIGNMENT = '=';
    
    private
        $dryRun,
        $isBackupEnabled,
        $environment;
    
    public function __construct(Application $app)
    {
        parent::__construct($app);
        
        $this->dryRun = false;
        $this->isBackupEnabled = false;
        
        $this->environment = self::ENV_DEV;
    }
    
    protected function configure()
    {
        parent::configure();
        
        $this
            ->setName('hydrate')
            ->setDescription('Hydrate dist files')
            
            ->addArgument('sourcePath', InputArgument::REQUIRED, 'source path to hydrate')
            
            ->addOption('env', 'e', InputOption::VALUE_REQUIRED, 'Target environment', self::ENV_DEV)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simulation mode')
            ->addOption('backup', 'b', InputOption::VALUE_NONE, 'Backup overwritten files')
            ->addOption('override', 'o', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Override variable values', array())
            ->addOption('data', 'd', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Custom data values', array())
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);
        
        $this->processInputs($input);
        $this->launchHydration();
    }
    
    private function processInputs(InputInterface $input)
    {
        $this->environment = $input->getOption('env'); 
        
        $this->output->writeln(sprintf(
            "<info>Hydrate <comment>%s</comment> with <comment>%s</comment> values</info>",
            $input->getArgument('sourcePath'),
            $this->environment
        ));
        
        if($input->getOption('dry-run'))
        {
            $this->dryRun = true;
            $this->output->writeln("<fg=cyan>Run in dry-run mode</fg=cyan>");
        }
        
        if($input->getOption('backup'))
        {
            $this->isBackupEnabled = true;
            $this->output->writeln("<fg=cyan>Backup enabled</fg=cyan>");
        }
        
        $this->output->writeln('');
        
        $this->app['sources.path'] = $input->getArgument('sourcePath');
        
        $this->processOverridenVariables(
            $this->parseOptionWithAssignments($input, 'override')
        );
        $this->processCustomData(
            $this->parseOptionWithAssignments($input, 'data')
        );
    }
    
    private function launchHydration()
    {
        $hydrator = $this->app['hydrator'];
        
        if($this->dryRun === true)
        {
            $hydrator->setDryRun();
        }
        
        if($this->isBackupEnabled === true)
        {
            $hydrator->enableBackup();
        }
            
        $hydrator->hydrate($this->environment);
    }
    
    private function parseOptionWithAssignments(InputInterface $input, $optionName)
    {
        $strings = $input->getOption($optionName);

        if(! is_array($strings))
        {
            $strings = array($strings);
        }
        
        $data = array();
        
        foreach($strings as $string)
        {
            if(stripos($string, self::OPTION_ASSIGNMENT) === false)
            {
                throw new \InvalidArgumentException(sprintf(
                    '%s option must contain %c : --%s <variable>=<value>',
                    $optionName,
                    self::OPTION_ASSIGNMENT,
                    $optionName                        
                ));    
            }

            list($variable, $value) = explode(self::OPTION_ASSIGNMENT, $string, 2);
            
            if(array_key_exists($variable, $data))
            {
                throw new \InvalidArgumentException("Duplicated %s option value : $variable");    
            }
            
            $data[$variable] = $value;
        }

        return $data;
    }
    
    private function processOverridenVariables(array $overrides)
    {
        $reader = $this->app['configuration'];
        $logger = $this->app['logger'];

        foreach($overrides as $variable => $value)
        {
            $logger->info(sprintf(
               'Set <important>%s</important> with value <important>%s</important>',
               $variable,
               $value
            ));
            
            $reader->overrideVariable($variable, $this->filterValue($value));
        }
    }
    
    private function processCustomData(array $data)
    {
        $reader = $this->app['configuration'];
        $logger = $this->app['logger'];

        foreach($data as $variable => $value)
        {
            $logger->info(sprintf(
               'Set custom data <important>%s</important> with value <important>%s</important>',
               $variable,
               $value
            ));
            
            $reader->setCustomData($variable, $this->filterValue($value));
        }
    }
}