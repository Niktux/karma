<?php

declare(strict_types = 1);

namespace Karma\Console;

require_once __DIR__ . '/CommandTestCase.php';

use Karma\Filesystem\Adapters\Memory;
use Gaufrette\Filesystem;
use Karma\Application;
use Karma\Hydrator;
use PHPUnit\Framework\Attributes\DataProvider;

class HydrateTest extends CommandTestCase
{
    private const string
        COMMAND_NAME = 'hydrate';

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['sources.fileSystem.adapter'] = new Memory(array(
            'src/file-dist' => '<%app.foo%>',
        ));

        $this->app['target.fileSystem.adapter'] = new Memory();
    }

    #[DataProvider('providerTestOptions')]
    public function testOptions(string $option, string $expectedMethodCall): void
    {
        $mock = $this->createMock(Hydrator::class);

        $mock->expects(self::once())
            ->method($expectedMethodCall);

        $this->app['hydrator'] = $mock;

        $this->runCommand(self::COMMAND_NAME, [
            $option => true,
            'sourcePath' => ['src/', 'settings/'],
        ]);
    }

    public static function providerTestOptions(): array
    {
        return [
            ['--dry-run', 'setDryRun'],
            ['--backup', 'enableBackup'],
        ];
    }

    public function testSourcePathFromProfile(): void
    {
        $this->app['profile.fileSystem.adapter'] = new Memory([
            Application::PROFILE_FILENAME => 'sourcePath: lib/',
        ]);

        $this->runCommand(self::COMMAND_NAME, []);
        $this->assertDisplay('~Hydrate lib/~');
    }

    public function testSourcePathsFromProfile(): void
    {
        $profileContent = <<<YAML
sourcePath:
    - lib/
    - settings/
YAML;
        
        $this->app['profile.fileSystem.adapter'] = new Memory([
            Application::PROFILE_FILENAME => $profileContent,
        ]);

        $this->runCommand(self::COMMAND_NAME, []);
        $this->assertDisplay('~Hydrate lib/ settings/~');
    }

    public function testNoSourcePathProvided(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->app['profile.fileSystem.adapter'] = new Memory();

        $this->runCommand(self::COMMAND_NAME, []);
    }

    public function testCache(): void
    {
        $cacheAdapter = new Memory([]);
        $this->app['finder.cache.adapter'] = $cacheAdapter;

        $cache = new Filesystem($cacheAdapter);
        self::assertEmpty($cache->keys());

        // exec without cache
        $this->runCommand(self::COMMAND_NAME, [
            'sourcePath' => 'src/',
        ]);

        self::assertEmpty($cache->keys());

        // exec with cache
        $this->runCommand(self::COMMAND_NAME, [
            '--cache' => true,
            'sourcePath' => 'src/',
        ]);

        self::assertNotEmpty($cache->keys());
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
        $this->app['sources.fileSystem.adapter'] = $adapter = new Memory([
            'src/file-dist' => '<%foo%>',
        ]);
        
        $this->app['target.fileSystem.adapter'] = $adapter = new Memory();

        $this->runCommand(self::COMMAND_NAME, [
            '--override' => ['foo=[1,2,3]'],
            'sourcePath' => 'src/',
        ]);

        $expected = "1\n2\n3";
        self::assertSame($expected, $adapter->read(('src/file')));
    }

    public function testDuplicatedOverrideOption(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->runCommand(self::COMMAND_NAME, [
            '--override' => ['db.user=toto', 'other=value', 'db.user=tata'],
            'sourcePath' => 'src/',
        ]);
    }

    public function testInvalidOverrideOption(): void
    {
        $this->expectException(\InvalidArgumentException::class);

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

    public function testDuplicatedCustomData(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->runCommand(self::COMMAND_NAME, [
            '--data' => ['user=toto', 'other=value', 'user=tata'],
            'sourcePath' => 'src/',
        ]);
    }

    public function testInvalidCustomData(): void
    {
        $this->expectException(\InvalidArgumentException::class);

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

    public function testTodo(): void
    {
        $this->runCommand(self::COMMAND_NAME, [
            '--override' => ['app.foo=__TODO__'],
            'sourcePath' => 'src/',
        ]);

        $this->assertDisplay('~Missing value.*app.foo~');
    }
}
