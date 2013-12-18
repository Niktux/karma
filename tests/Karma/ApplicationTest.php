<?php

use Karma\Application;

class ApplicationTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider providerTestContainer
     */
    public function testContainer($service, $expected)
    {
        $app = new Application();
        
        $this->assertInstanceOf($expected, $app[$service]);
    }
    
    public function providerTestContainer()
    {
        return array(
        	array('hydrator', 'Karma\\Hydrator'),
        	array('parser', 'Karma\\Configuration\\Parser'),
        	array('configuration', 'Karma\\Configuration'),
        );
    }
}