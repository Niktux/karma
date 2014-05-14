<?php

require_once __DIR__ . '/CommandTestCase.php';

use Gaufrette\Adapter\InMemory;
use Karma\Application;

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
    
    public function testSourcePathFromProfile()
    {
        $this->app['profile.fileSystem.adapter'] = new InMemory(array(
            Application::PROFILE_FILENAME => 'sourcePath: lib/',
        ));
    
        $this->runCommand('vcs', array());
    }
    
    /**
     * @expectedException \RuntimeException
     */
    public function testNoSourcePathProvided()
    {
        $this->app['profile.fileSystem.adapter'] = new InMemory();
    
        $this->runCommand('vcs', array());
    }
}