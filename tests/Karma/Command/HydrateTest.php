<?php

use Gaufrette\Adapter\InMemory;
require_once __DIR__ . '/CommandTestCase.php';

class HydrateTest extends CommandTestCase
{
    protected
        $mock;
    
    protected function setUp()
    {
        parent::setUp();
        
        $this->app['sources.fileSystem.adapter'] = new InMemory(array(
        	'src/file' => '',
        ));

        $this->mock = $this->getMock(
            'Karma\Hydrator',
            array(),
            array($this->app['sources.fileSystem'], $this->app['configuration'])
        );
    }
    
    /**
     * @dataProvider providerTestOptions
     */
    public function testOptions($option, $expectedMethodCall)
    {
        $this->mock->expects($this->once())
            ->method($expectedMethodCall);
        
        $this->app['hydrator'] = $this->mock;
        
        $this->runCommand('hydrate', array(
            $option => true,
            'sourcePath' => 'src/'
        ));
    }
    
    public function providerTestOptions()
    {
        return array(
        	array('--dry-run', 'setDryRun'),
        	array('--backup', 'enableBackup'),
        );
    }
}