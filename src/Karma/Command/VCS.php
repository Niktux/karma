<?php

namespace Karma\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Karma\Command;
use Karma\Application;
use Symfony\Component\Console\Input\InputOption;

class VCS extends Command
{
    private
        $vcs;
    
    public function __construct(Application $app)
    {
        parent::__construct($app);
        
        $this->vcs = $app['vcsHandler']($app['git']);
    }
    
    protected function configure()
    {
        parent::configure();
        
        $this
            ->setName('vcs')
            ->setDescription('')
            ->addOption('suffix', null, InputOption::VALUE_REQUIRED, 'File suffix', null)
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);
        
        $suffix = $input->getOption('suffix');
        if($suffix === null)
        {
            $suffix = Application::DEFAULT_DISTFILE_SUFFIX;
        
            $profile = $this->app['profile'];
            if($profile->hasTemplatesSuffix())
            {
                $suffix = $profile->getTemplatesSuffix();
            }
        }
        
        $this->output->writeln('Looking for vcs operations');
        
        $this->app['distFiles.suffix'] = $suffix;
        
        $this->vcs->execute();
    }
}