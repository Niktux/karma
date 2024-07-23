<?php

declare(strict_types = 1);

namespace Karma\Console;

require_once __DIR__ . '/CommandTestCase.php';

use Gaufrette\Adapter\InMemory;
use Gaufrette\Filesystem;
use Karma\Application;
use Karma\Hydrator;
use PHPUnit\Framework\Attributes\DataProvider;

class RollbackTest extends CommandTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app['sources.fileSystem.adapter'] = new InMemory([
        	'src/file' => '',
        ]);
    }

    #[DataProvider('providerTestOptions')]
    public function testOptions($option, $expectedMethodCall): void
    {
        $mock = $this->createMock(Hydrator::class);

        $mock->expects(self::once())
            ->method($expectedMethodCall);

        $this->app['hydrator'] = $mock;

        $this->runCommand('rollback', [
            $option => true,
            'sourcePath' => 'src/'
        ]);
    }

    public static function providerTestOptions(): array
    {
        return [
            ['--dry-run', 'setDryRun'],
        ];
    }

    public function testCache(): void
    {
        $cacheAdapter = new InMemory([]);
        $this->app['finder.cache.adapter'] = $cacheAdapter;

        $cache = new Filesystem($cacheAdapter);
        self::assertEmpty($cache->keys());

        // exec without cache
        $this->runCommand('rollback', [
            'sourcePath' => 'src/',
        ]);

        self::assertEmpty($cache->keys());

        // exec with cache
        $this->runCommand('rollback', [
            '--cache' => true,
            'sourcePath' => 'src/',
        ]);

        self::assertNotEmpty($cache->keys());
    }

    public function testSourcePathFromProfile(): void
    {
        $this->app['profile.fileSystem.adapter'] = new InMemory([
            Application::PROFILE_FILENAME => 'sourcePath: lib/',
        ]);

        $this->runCommand('rollback', []);
        $this->assertDisplay('~Rollback lib/~');
    }

    public function testNoSourcePathProvided(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->app['profile.fileSystem.adapter'] = new InMemory();

        $this->runCommand('rollback', []);
    }
}
