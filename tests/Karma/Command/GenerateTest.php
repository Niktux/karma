<?php

declare(strict_types = 1);

namespace Karma\Command;

require_once __DIR__ . '/CommandTestCase.php';

use Gaufrette\Adapter\InMemory;
use Karma\Application;
use Karma\Generator\ConfigurationFileGenerators\YamlGenerator;

class GenerateTest extends CommandTestCase
{
    const
        COMMAND_NAME = 'generate';

    protected function setUp()
    {
        parent::setUp();

        $this->app['generate.sources.fileSystem.adapter'] = new InMemory([
            'src/file' => '',
        ]);

        $this->app['profile.fileSystem.adapter'] = new InMemory([
            Application::PROFILE_FILENAME => "generator:\n  translator: none",
        ]);
    }

    /**
     * @dataProvider providerTestOptions
     */
    public function testOptions($option, $expectedMethodCall)
    {
        $mock = $this->createMock(YamlGenerator::class);

        $mock->expects($this->once())
            ->method($expectedMethodCall);

        $this->app['configurationFilesGenerator'] = $mock;

        $this->runCommand(self::COMMAND_NAME, [
            $option => true,
            'sourcePath' => 'src/',
        ]);
    }

    public function providerTestOptions()
    {
        return [
            ['--dry-run', 'setDryRun'],
            ['--backup', 'enableBackup'],
        ];
    }

    public function testSourcePathFromProfile()
    {
        $this->app['profile.fileSystem.adapter'] = new InMemory([
            Application::PROFILE_FILENAME => 'sourcePath: lib/',
        ]);

        $this->runCommand(self::COMMAND_NAME, []);
        $this->assertDisplay('~Generate configuration files in lib/~');
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testNoSourcePathProvided()
    {
        $this->app['profile.fileSystem.adapter'] = new InMemory();

        $this->runCommand(self::COMMAND_NAME, []);
    }

    public function testOverride()
    {
        $this->runCommand(self::COMMAND_NAME, [
            '--override' => ['db.user=toto', 'api.key=azer=ty'],
            'sourcePath' => 'src/',
        ]);

        $this->assertDisplay('~Override db.user with value toto~');
        $this->assertDisplay('~Override api.key with value azer=ty~');
    }

    public function testOverrideWithList()
    {
        $this->app['generate.sources.fileSystem.adapter'] = $adapter = new InMemory([
        ]);

        $this->runCommand(self::COMMAND_NAME, [
            '--override' => ['app.foo=[1,2,3]'],
            'sourcePath' => 'src/',
        ]);

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
        $this->runCommand(self::COMMAND_NAME, [
            '--override' => ['db.user=toto', 'other=value', 'db.user=tata'],
            'sourcePath' => 'src/',
        ]);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidOverrideOption()
    {
        $this->runCommand(self::COMMAND_NAME, [
            '--override' => 'db.user:tata',
            'sourcePath' => 'src/',
        ]);
    }

    public function testCustomData()
    {
        $this->runCommand(self::COMMAND_NAME, [
            '--data' => ['user=jdoe', 'api.key=azer=ty'],
            'sourcePath' => 'src/',
        ]);

        $this->assertDisplay('~Set custom data user with value jdoe~');
        $this->assertDisplay('~Set custom data api.key with value azer=ty~');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testDuplicatedCustomData()
    {
        $this->runCommand(self::COMMAND_NAME, [
            '--data' => ['user=toto', 'other=value', 'user=tata'],
            'sourcePath' => 'src/',
        ]);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidCustomData()
    {
        $this->runCommand(self::COMMAND_NAME, [
            '--data' => 'db.user:tata',
            'sourcePath' => 'src/',
        ]);
    }

    public function testSystemEnvironment()
    {
        $this->runCommand(self::COMMAND_NAME, [
            '--system' => 'dev',
            'sourcePath' => 'src/',
        ]);

        $this->assertDisplay('~Hydrate system variables with dev values~');
    }
}
