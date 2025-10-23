<?php

declare(strict_types = 1);

namespace Karma;

use Karma\Filesystem\Adapters\Memory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase
{
    #[DataProvider('providerTestContainer')]
    public function testContainer(string $service, string $expected): void
    {
        $adapter = new Memory();
        $adapter->write(Application::DEFAULT_MASTER_FILE, null);

        $app = new Application();
        $app['configuration.fileSystem.adapter'] = $adapter;

        self::assertInstanceOf($expected, $app[$service]);
    }

    public static function providerTestContainer(): array
    {
        return [
           'hydrator' =>
               ['hydrator', Hydrator::class],
           'parser' =>
               ['parser', Configuration\Parser::class],
           'config' =>
               ['configuration', Configuration::class],
           'finder' =>
               ['finder', Finder::class],
           'configFileGen' =>
               ['configurationFilesGenerator', Generator\ConfigurationFileGenerator::class],
        ];
    }
}
