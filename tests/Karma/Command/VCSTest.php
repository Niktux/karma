<?php

require_once __DIR__ . '/CommandTestCase.php';

use Gaufrette\Adapter\InMemory;

class VCSTest extends CommandTestCase
{
    protected function setUp()
    {
        parent::setUp();
    
        $this->app['vcs'] = new \Karma\VCS\InMemory('~tracked~', '~ignored~');
        $this->app['sources.fileSystem.adapter'] = new InMemory(array(
            'config/app.yml-dist' => '',
        ));
    }
    
    public function testVcs()
    {
        $this->runCommand('vcs', array(
            'sourcePath' => 'config/'
        ));
        
        $this->assertDisplay('~Looking for vcs~');
    }
}