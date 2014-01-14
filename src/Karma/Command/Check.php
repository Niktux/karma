<?php

namespace Karma\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Karma\Command;
use Karma\Application;
use Gaufrette\Adapter\iterator_to_array;
use Karma\Hydrator;

class Check extends Command
{
    private
        $distFiles;
    
    protected function configure()
    {
        parent::configure();
        
        $this
            ->setName('check')
            ->setDescription('Performs sanity checks')
            
            ->addArgument('sourcePath', InputArgument::REQUIRED, 'source path to hydrate')
            ->addOption('suffix', null, InputOption::VALUE_REQUIRED, 'File suffix', Application::DEFAULT_DISTFILE_SUFFIX)
        ;
    }
    
    private function collectDistFiles()
    {
        $this->distFiles = $this->app['finder']->findFiles($this->app['distFiles.suffix']);
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);
        
        $this->app['sources.path']     = $input->getArgument('sourcePath');
        $this->app['distFiles.suffix'] = $input->getOption('suffix');
        $this->collectDistFiles();
        
        $this->info("1 - Check dist files\n");
        $this->checkForDistFilesWithoutVariables();
        $this->output->writeln('');

        $this->info("2 - Not valued variables\n");
        $this->checkForNotValuedVariables();
        $this->output->writeln('');
    }
    
    private function checkForDistFilesWithoutVariables()
    {
        $fs = $this->app['sources.fileSystem'];
        
        foreach($this->distFiles as $file)
        {
            $content = $fs->read($file);
            if(preg_match(Hydrator::VARIABLE_REGEX, $content) === 0)
            {
                $this->warning("$file does not contain variables\n");
            }    
        }
    }

    private function checkForNotValuedVariables()
    {
        $fs = $this->app['sources.fileSystem'];
        $reader = $this->app['configuration'];
        
        foreach($this->distFiles as $file)
        {
            $content = $fs->read($file);
            if(preg_match_all(Hydrator::VARIABLE_REGEX, $content, $matches))
            {
                $missingVariables = array_diff($matches['variableName'], $reader->getAllVariables());
                
                foreach($missingVariables as $variable)
                {
                    $this->error("Variable $variable is not declared");
                }
            }
        }        
    }
}