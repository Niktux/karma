<?php

namespace Karma\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Karma\Command;
use Karma\Application;

class VCS extends Command
{
    private
        $vcs;
    
    public function __construct(Application $app, \Karma\VCS\Vcs $vcs)
    {
        parent::__construct($app);
        
        $this->vcs = $vcs;
    }
    
    protected function configure()
    {
        parent::configure();
        
        $this
            ->setName('vcs')
            ->setDescription('')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $result = $this->vcs->isTracked('src/Karma/Finder.php');
        
        var_dump($result);
    }
}