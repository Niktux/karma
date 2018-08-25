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
    public function testContainer($service, $expected)
    {
        $adapter = new InMemory();
        $adapter->write(Application::DEFAULT_MASTER_FILE, null);

        $app = new Application();
        $app['configuration.fileSystem.adapter'] = $adapter;

        $this->assertInstanceOf($expected, $app[$service]);
    }

    public function providerTestContainer()
    {
        return [
            ['hydrator', 'Karma\\Hydrator'],
            ['parser', 'Karma\\Configuration\\Parser'],
            ['configuration', 'Karma\\Configuration'],
            ['finder', 'Karma\\Finder'],
            ['configurationFilesGenerator', 'Karma\\Generator\\ConfigurationFileGenerator'],
        ];
    }
}
