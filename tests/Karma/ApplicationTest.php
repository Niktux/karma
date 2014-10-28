<?php

namespace Karma;

use Gaufrette\Adapter\InMemory;

class ApplicationTest extends \PHPUnit_Framework_TestCase
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
        return array(
            array('hydrator', 'Karma\\Hydrator'),
            array('parser', 'Karma\\Configuration\\Parser'),
            array('configuration', 'Karma\\Configuration'),
            array('finder', 'Karma\\Finder'),
        );
    }
}
