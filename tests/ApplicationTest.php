<?php

declare(strict_types = 1);

namespace Karma;

use Gaufrette\Adapter\InMemory;
use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase
{
    /**
     * @dataProvider providerTestContainer
     */
    public function testContainer(string $service, string $expected): void
    {
        $adapter = new InMemory();
        $adapter->write(Application::DEFAULT_MASTER_FILE, null);

        $app = new Application();
        $app['configuration.fileSystem.adapter'] = $adapter;

        self::assertInstanceOf($expected, $app[$service]);
    }

    public function providerTestContainer(): array
    {
        return [
            ['hydrator', Hydrator::class],
            ['parser', Configuration\Parser::class],
            ['configuration', Configuration::class],
            ['finder', Finder::class],
            ['configurationFilesGenerator', Generator\ConfigurationFileGenerator::class],
        ];
    }
}
