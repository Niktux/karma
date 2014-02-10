<?php

use Karma\VcsHandler;
use Karma\Finder;
use Gaufrette\Filesystem;
use Gaufrette\Adapter\InMemory;

class VcsHandlerTest extends PHPUnit_Framework_TestCase
{
    private
        $fs,
        $vcs,
        $handler;
    
    protected function setUp()
    {
        $files = array(
        	'a.yml-dist' => null,
        	'path/to/b.yml-dist' => null,
        	'some/path/to/tracked.yml-dist' => null,
        	'trackedAndIgnored.yml-dist' => null,
        	'ignored.yml-dist' => null,
            'anotherFile.ini-tpl' => null,
        );
        
        $this->fs = new Filesystem(new InMemory($files));
        $finder = new Finder($this->fs);

        $this->vcs = new \Karma\VCS\InMemory('~tracked~i', '~ignored~i');
        $this->handler = new VcsHandler($this->vcs, $finder);
    }
    
    public function testExecute()
    {
        $this->handler->execute();
        
        $expectedUntrackedFiles = array(
            'some/path/to/tracked.yml',
            'trackedAndIgnored.yml'
        );
        $this->assertSameArrayExceptOrder($expectedUntrackedFiles, $this->vcs->getUntrackedFiles());
        
        $expectedIgnoredFiles = array(
        	'a.yml',
        	'path/to/b.yml',
        	'some/path/to/tracked.yml',
        );
        $this->assertSameArrayExceptOrder($expectedIgnoredFiles, $this->vcs->getIgnoredFiles());
    }
    
    private function assertSameArrayExceptOrder(array $expected, array $values)
    {
        $this->assertCount(count($expected), $values);
        
        foreach($expected as $expectedValue)
        {
            $this->assertContains($expectedValue, $values);
        }
    }
    
    public function testSetSuffix()
    {
        $this->handler
            ->setSuffix('-tpl')
            ->execute();
        
        $this->assertSameArrayExceptOrder(array('anotherFile.ini'), $this->vcs->getIgnoredFiles());
    }
}