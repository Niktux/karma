<?php

declare(strict_types = 1);

namespace Karma\Console;

require_once __DIR__ . '/CommandTestCase.php';

use Gaufrette\Adapter\InMemory;
use Karma\Application;
use Karma\Generator\ConfigurationFileGenerators\YamlGenerator;

class GenerateTest extends CommandTestCase
{
    private const
        COMMAND_NAME = 'generate';

    protected function setUp(): void
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
    public function testOptions(string $option, string $expectedMethodCall): void
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

    public function providerTestOptions(): array
    {
        return [
            ['--dry-run', 'setDryRun'],
            ['--backup', 'enableBackup'],
        ];
    }

    public function testSourcePathFromProfile(): void
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
    public function testNoSourcePathProvided(): void
    {
        $this->app['profile.fileSystem.adapter'] = new InMemory();

        $this->runCommand(self::COMMAND_NAME, []);
    }

    public function testOverride(): void
    {
        $this->runCommand(self::COMMAND_NAME, [
            '--override' => ['db.user=toto', 'api.key=azer=ty'],
            'sourcePath' => 'src/',
        ]);

        $this->assertDisplay('~Override db.user with value toto~');
        $this->assertDisplay('~Override api.key with value azer=ty~');
    }

    public function testOverrideWithList(): void
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
        $this->assertSame($expected, $adapter->read('app.yml'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testDuplicatedOverrideOption(): void
    {
        $this->runCommand(self::COMMAND_NAME, [
            '--override' => ['db.user=toto', 'other=value', 'db.user=tata'],
            'sourcePath' => 'src/',
        ]);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidOverrideOption(): void
    {
        $this->runCommand(self::COMMAND_NAME, [
            '--override' => 'db.user:tata',
            'sourcePath' => 'src/',
        ]);
    }

    public function testCustomData(): void
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
    public function testDuplicatedCustomData(): void
    {
        $this->runCommand(self::COMMAND_NAME, [
            '--data' => ['user=toto', 'other=value', 'user=tata'],
            'sourcePath' => 'src/',
        ]);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidCustomData(): void
    {
        $this->runCommand(self::COMMAND_NAME, [
            '--data' => 'db.user:tata',
            'sourcePath' => 'src/',
        ]);
    }

    public function testSystemEnvironment(): void
    {
        $this->runCommand(self::COMMAND_NAME, [
            '--system' => 'dev',
            'sourcePath' => 'src/',
        ]);

        $this->assertDisplay('~Hydrate system variables with dev values~');
    }
}
