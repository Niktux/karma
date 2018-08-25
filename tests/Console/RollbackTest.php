<?php

declare(strict_types = 1);

namespace Karma\Console;

require_once __DIR__ . '/CommandTestCase.php';

use Gaufrette\Adapter\InMemory;
use Gaufrette\Filesystem;
use Karma\Application;
use Karma\Hydrator;

class RollbackTest extends CommandTestCase
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
        $mock = $this->createMock(Hydrator::class);

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

    public function testCache()
    {
        $cacheAdapter = new InMemory(array());
        $this->app['finder.cache.adapter'] = $cacheAdapter;

        $cache = new Filesystem($cacheAdapter);
        $this->assertEmpty($cache->keys());

        // exec without cache
        $this->runCommand('rollback', array(
            'sourcePath' => 'src/',
        ));

        $this->assertEmpty($cache->keys());

        // exec with cache
        $this->runCommand('rollback', array(
            '--cache' => true,
            'sourcePath' => 'src/',
        ));

        $this->assertNotEmpty($cache->keys());
    }

    public function testSourcePathFromProfile()
    {
        $this->app['profile.fileSystem.adapter'] = new InMemory(array(
            Application::PROFILE_FILENAME => 'sourcePath: lib/',
        ));

        $this->runCommand('rollback', array());
        $this->assertDisplay('~Rollback lib/~');
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testNoSourcePathProvided()
    {
        $this->app['profile.fileSystem.adapter'] = new InMemory();

        $this->runCommand('rollback', array());
    }
}
