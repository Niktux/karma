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
use Karma\Configuration\ValueFilter;
use Symfony\Component\Console\Input\InputArgument;
use Karma\Display\CliTable;

class Diff extends Command
{
    use \Karma\Logging\OutputAware;
    
    protected function configure()
    {
        $this
            ->setName('diff')
            ->setDescription('Display differences between environment variable set')
            
            ->addOption('confDir', null, InputOption::VALUE_REQUIRED, 'Configuration root directory', Application::DEFAULT_CONF_DIRECTORY)
            ->addOption('master',  null, InputOption::VALUE_REQUIRED, 'Configuration master file',    Application::DEFAULT_MASTER_FILE)
            ->addArgument('env1', InputArgument::REQUIRED, 'First environment to compare')
            ->addArgument('env2', InputArgument::REQUIRED, 'Second environment to compare')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setOutput($output);
        
        $environment1 = $input->getArgument('env1'); 
        $environment2 = $input->getArgument('env2'); 
        
        $this->output->writeln(sprintf(
            '<info>Diif between <comment>%s</comment> and <comment>%s</comment></info>',
            $environment1,
            $environment2
        ));
        
        $app = new \Karma\Application();
        $app['configuration.path']       = $input->getOption('confDir');
        $app['configuration.masterFile'] = $input->getOption('master');
        
        $reader = $app['configuration'];
        
        $diff = $this->getDifferentValues($reader, $environment1, $environment2);
        
        $table = new CliTable($diff);
        $output->writeln($table->render());
    }
    
    private function getDifferentValues($reader, $environment1, $environment2)
    {
        $values1 = $reader->getAllValuesForEnvironment($environment1);
        $values2 = $reader->getAllValuesForEnvironment($environment2);
        
        $table = array();
        $table[] = array('', $environment1, $environment2);
        
        foreach($values1 as $name => $value1)
        {
            $value2 = $values2[$name];
            
            if($value1 !== $value2)
            {
                $table[] = array($name, $value1, $value2);
            }
        }
        
        return $table;
    }
}