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
use Karma\Configuration\InMemoryReader;
use Karma\Application;
use Karma\Logging\OutputInterfaceAdapter;

class Hydrate extends Command
{
    use \Karma\Logging\OutputAware;
    
    const
        ENV_DEV = 'dev';
    
    protected function configure()
    {
        $this
            ->setName('hydrate')
            ->setDescription('Hydrate dist files')
            
            ->addArgument('sourcePath', InputArgument::REQUIRED, 'source path to hydrate')
            
            ->addOption('env',     null, InputOption::VALUE_REQUIRED, 'Target environment',           self::ENV_DEV)
            ->addOption('suffix',  null, InputOption::VALUE_REQUIRED, 'File suffix',                  Application::DEFAULT_DISTFILE_SUFFIX)
            ->addOption('confDir', null, InputOption::VALUE_REQUIRED, 'Configuration root directory', Application::DEFAULT_CONF_DIRECTORY)
            ->addOption('master',  null, InputOption::VALUE_REQUIRED, 'Configuration master file',    Application::DEFAULT_MASTER_FILE)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simulation mode')
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
        
        $app = new \Karma\Application();
        $app['sources.path']             = $input->getArgument('sourcePath');
        $app['distFiles.suffix']         = $input->getOption('suffix');
        $app['configuration.path']       = $input->getOption('confDir');
        $app['configuration.masterFile'] = $input->getOption('master');
        
        $hydrator = $app['hydrator'];
        $hydrator->setLogger(new OutputInterfaceAdapter($output));
        
        if($input->hasOption('dry-run'))
        {
            $this->output->writeln("<fg=cyan>*** Run in dry-run mode ***</fg=cyan>");
            $hydrator->setDryRun();
        }
            
        $hydrator->hydrate($environment);
    }
}