<?php

namespace Karma\Command;

require_once __DIR__ . '/CommandTestCase.php';

use Gaufrette\Adapter\InMemory;
use Gaufrette\Filesystem;
use Karma\Application;

class GenerateTest extends CommandTestCase
{
    const
        COMMAND_NAME = 'generate';

    protected function setUp()
    {
        parent::setUp();

        $this->app['sources.fileSystem.adapter'] = new InMemory(array(
            'src/file' => '',
        ));

        $this->app['profile.fileSystem.adapter'] = new InMemory(array(
            Application::PROFILE_FILENAME => "generator:\n  translator: none",
        ));
    }

    /**
     * @dataProvider providerTestOptions
     */
    public function testOptions($option, $expectedMethodCall)
    {
        $mock = $this->getMockBuilder('Karma\Generator\ConfigurationFileGenerators\YamlGenerator')->setConstructorArgs([
            $this->app['sources.fileSystem'], $this->app['configuration'], $this->app['generator.variableProvider']
        ])->getMock();

        $mock->expects($this->once())
            ->method($expectedMethodCall);

        $this->app['configurationFilesGenerator'] = $mock;

        $this->runCommand(self::COMMAND_NAME, array(
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

    public function testSourcePathFromProfile()
    {
        $this->app['profile.fileSystem.adapter'] = new InMemory(array(
            Application::PROFILE_FILENAME => 'sourcePath: lib/',
        ));

        $this->runCommand(self::COMMAND_NAME, array());
        $this->assertDisplay('~Generate configuration files in lib/~');
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testNoSourcePathProvided()
    {
        $this->app['profile.fileSystem.adapter'] = new InMemory();

        $this->runCommand(self::COMMAND_NAME, array());
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
        ));

        $this->runCommand(self::COMMAND_NAME, array(
            '--override' => array('app.foo=[1,2,3]'),
            'sourcePath' => 'src/',
        ));

        $expected = <<<YAML
foo:
    - 1
    - 2
    - 3
bar: valueAll

YAML;
        $this->assertSame($expected, $adapter->read(('app.yml')));
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
}
