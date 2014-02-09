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
    
    public function __construct(Application $app)
    {
        parent::__construct($app);
        
        $this->vcs = $app['git'];
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

        $filepath = 'src/Karma/Finder.php';
        $result = $this->vcs->isTracked($filepath);
        var_dump($result);
        
        $this->vcs->isIgnored($filepath);
    }
}