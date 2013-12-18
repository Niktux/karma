<?php

use Karma\Configuration\InMemoryReader;

class InMemoryReaderTest extends PHPUnit_Framework_TestCase
{
    private
        $reader;
    
    protected function setUp()
    {
        $this->reader = new InMemoryReader(array(
            'foo:dev' => 'foodev',
            'foo:prod' => 'fooprod',
            'bar:dev' => 'bardev',
            'baz:recette' => 'bazrecette',
        ));
    }
    
    /**
     * @dataProvider providerTestRead
     */
    public function testRead($variable, $environment, $expected)
    {
        $this->assertSame($expected, $this->reader->read($variable, $environment));    
    }
    
    /**
     * @dataProvider providerTestRead
     */
    public function testReadWithDefaultEnvironment($variable, $environment, $expected)
    {
        $this->reader->setDefaultEnvironment($environment);
        $this->assertSame($expected, $this->reader->read($variable));
    }
    
    public function providerTestRead()
    {
        return array(
            array('baz', 'recette', 'bazrecette'),
            array('bar', 'dev', 'bardev'),
            array('foo', 'prod', 'fooprod'),
            array('foo', 'dev', 'foodev'),
            array('donotexist', 'dev', null),
        );
    }
}