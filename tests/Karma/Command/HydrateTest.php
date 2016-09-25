<?php

namespace Karma\Command;

require_once __DIR__ . '/CommandTestCase.php';

use Gaufrette\Adapter\InMemory;
use Gaufrette\Filesystem;
use Karma\Application;

class HydrateTest extends CommandTestCase
{
    const
        COMMAND_NAME = 'hydrate';

    protected function setUp()
    {
        parent::setUp();

        $this->app['sources.fileSystem.adapter'] = new InMemory(array(
            'src/file-dist' => '<%app.foo%>',
        ));

        $this->app['target.fileSystem.adapter'] = new InMemory();
    }

    /**
     * @dataProvider providerTestOptions
     */
    public function testOptions($option, $expectedMethodCall)
    {
        $mock = $this->getMock(
            'Karma\Hydrator',
            array(),
            array($this->app['sources.fileSystem'], $this->app['target.fileSystem'], $this->app['configuration'], $this->app['finder'])
        );

        $mock->expects($this->once())
            ->method($expectedMethodCall);

        $this->app['hydrator'] = $mock;

        $this->runCommand(self::COMMAND_NAME, array(
            $option => true,
            'sourcePath' => ['src/', 'settings/'],
        ));
    }

    public function providerTestOptions()
    {
        return array(
            array('--dry-run', 'setDryRun'),
            array('--backup', 'enableBackup'),
        );
    }

    public function testSourcePathFromProfile()
    {
        $this->app['profile.fileSystem.adapter'] = new InMemory(array(
            Application::PROFILE_FILENAME => 'sourcePath: lib/',
        ));

        $this->runCommand(self::COMMAND_NAME, array());
        $this->assertDisplay('~Hydrate lib/~');
    }

    public function testSourcePathsFromProfile()
    {
        $profileContent = <<<YAML
sourcePath:
    - lib/
    - settings/
YAML;
        
        $this->app['profile.fileSystem.adapter'] = new InMemory(array(
            Application::PROFILE_FILENAME => $profileContent,
        ));

        $this->runCommand(self::COMMAND_NAME, array());
        $this->assertDisplay('~Hydrate lib/ settings/~');
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testNoSourcePathProvided()
    {
        $this->app['profile.fileSystem.adapter'] = new InMemory();

        $this->runCommand(self::COMMAND_NAME, array());
    }

    public function testCache()
    {
        $cacheAdapter = new InMemory(array());
        $this->app['finder.cache.adapter'] = $cacheAdapter;

        $cache = new Filesystem($cacheAdapter);
        $this->assertEmpty($cache->keys());

        // exec without cache
        $this->runCommand(self::COMMAND_NAME, array(
            'sourcePath' => 'src/',
        ));

        $this->assertEmpty($cache->keys());

        // exec with cache
        $this->runCommand(self::COMMAND_NAME, array(
            '--cache' => true,
            'sourcePath' => 'src/',
        ));

        $this->assertNotEmpty($cache->keys());
    }

    public function testOverride()
    {
        $this->runCommand(self::COMMAND_NAME, array(
            '--override' => array('db.user=toto', 'api.key=azer=ty'),
            'sourcePath' => 'src/',
        ));

        $this->assertDisplay('~Override db.user with value toto~');
        $this->assertDisplay('~Override api.key with value azer=ty~');
    }

    public function testOverrideWithList()
    {
        $this->app['sources.fileSystem.adapter'] = $adapter = new InMemory(array(
            'src/file-dist' => '<%foo%>',
        ));
        
        $this->app['target.fileSystem.adapter'] = $adapter = new InMemory();

        $this->runCommand(self::COMMAND_NAME, array(
            '--override' => array('foo=[1,2,3]'),
            'sourcePath' => 'src/',
        ));

        $expected = "1\n2\n3";
        $this->assertSame($expected, $adapter->read(('src/file')));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testDuplicatedOverrideOption()
    {
        $this->runCommand(self::COMMAND_NAME, array(
            '--override' => array('db.user=toto', 'other=value', 'db.user=tata'),
            'sourcePath' => 'src/',
        ));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidOverrideOption()
    {
        $this->runCommand(self::COMMAND_NAME, array(
            '--override' => 'db.user:tata',
            'sourcePath' => 'src/',
        ));
    }

    public function testCustomData()
    {
        $this->runCommand(self::COMMAND_NAME, array(
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
        $this->runCommand(self::COMMAND_NAME, array(
            '--data' => array('user=toto', 'other=value', 'user=tata'),
            'sourcePath' => 'src/',
        ));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidCustomData()
    {
        $this->runCommand(self::COMMAND_NAME, array(
            '--data' => 'db.user:tata',
            'sourcePath' => 'src/',
        ));
    }

    public function testSystemEnvironment()
    {
        $this->runCommand(self::COMMAND_NAME, array(
            '--system' => 'dev',
            'sourcePath' => 'src/',
        ));

        $this->assertDisplay('~Hydrate system variables with dev values~');
    }

    public function testTodo()
    {
        $this->runCommand(self::COMMAND_NAME, array(
            '--override' => array('app.foo=__TODO__'),
            'sourcePath' => 'src/',
        ));

        $this->assertDisplay('~Missing value.*app.foo~');
    }
}
