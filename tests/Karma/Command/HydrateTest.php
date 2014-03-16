<?php

require_once __DIR__ . '/CommandTestCase.php';

use Gaufrette\Adapter\InMemory;
use Gaufrette\Filesystem;
use Karma\Application;

class HydrateTest extends CommandTestCase
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
        
        $this->runCommand('hydrate', array(
            $option => true,
            'sourcePath' => 'src/',
        ));
    }
    
    public function providerTestOptions()
    {
        return array(
            array('--dry-run', 'setDryRun'),
            array('--backup', 'enableBackup'),
        );
    }
    
    public function testCache()
    {
        $cacheAdapter = new InMemory(array());
        $this->app['finder.cache.adapter'] = $cacheAdapter;
        
        $cache = new Filesystem($cacheAdapter);
        $this->assertEmpty($cache->keys());
        
        // exec without cache
        $this->runCommand('hydrate', array(
            'sourcePath' => 'src/',
        ));
        
        $this->assertEmpty($cache->keys());
        
        // exec with cache
        $this->runCommand('hydrate', array(
            '--cache' => true,
            'sourcePath' => 'src/',
        ));
        
        $this->assertNotEmpty($cache->keys());
    }
    
    public function testOverride()
    {
        $this->runCommand('hydrate', array(
            '--override' => array('db.user=toto', 'api.key=azer=ty'),
            'sourcePath' => 'src/',
        ));
        
        $this->assertDisplay('~Override db.user with value toto~');
        $this->assertDisplay('~Override api.key with value azer=ty~');
    }
    
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testDuplicatedOverrideOption()
    {
        $this->runCommand('hydrate', array(
            '--override' => array('db.user=toto', 'other=value', 'db.user=tata'),
            'sourcePath' => 'src/',
        ));
    }
    
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidOverrideOption()
    {
        $this->runCommand('hydrate', array(
            '--override' => 'db.user:tata',
            'sourcePath' => 'src/',
        ));
    }
    
    public function testCustomData()
    {
        $this->runCommand('hydrate', array(
            '--data' => array('user=jdoe', 'api.key=azer=ty'),
            'sourcePath' => 'src/',
        ));
        
        $this->assertDisplay('~Set custom data user with value jdoe~');
        $this->assertDisplay('~Set custom data api.key with value azer=ty~');
    }
    
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testDuplicatedCustomData()
    {
        $this->runCommand('hydrate', array(
            '--data' => array('user=toto', 'other=value', 'user=tata'),
            'sourcePath' => 'src/',
        ));
    }
    
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidCustomData()
    {
        $this->runCommand('hydrate', array(
            '--data' => 'db.user:tata',
            'sourcePath' => 'src/',
        ));
    }
}