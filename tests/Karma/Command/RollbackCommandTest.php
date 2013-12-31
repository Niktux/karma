<?php

require_once __DIR__ . '/CommandTestCase.php';

use Gaufrette\Adapter\InMemory;

class RollbackCommandTest extends CommandTestCase
{
    protected function setUp()
    {
        parent::setUp();
        
        $this->app['sources.fileSystem.adapter'] = new InMemory(array(
        	'src/file' => '',
        ));
    }
    
    /**
     * @dataProvider providerTestOptions
     */
    public function testOptions($option, $expectedMethodCall)
    {
        $mock = $this->getMock(
            'Karma\Hydrator',
            array(),
            array($this->app['sources.fileSystem'], $this->app['configuration'], $this->app['finder'])
        );
        $mock->expects($this->once())
            ->method($expectedMethodCall);
        
        $this->app['hydrator'] = $mock;
        
        $this->runCommand('rollback', array(
            $option => true,
            'sourcePath' => 'src/'
        ));
    }
    
    public function providerTestOptions()
    {
        return array(
        	array('--dry-run', 'setDryRun'),
        );
    }
}