<?php

use Karma\Configuration\InMemoryReader;
use Karma\Configuration;

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
    
    public function testGetAllVariables()
    {
        $variables = $this->reader->getAllVariables();
        sort($variables);
        
        $expected = array('foo', 'bar', 'baz');
        sort($expected);
        
        $this->assertSame($expected, $variables);
    }
    
    /**
     * @dataProvider providerTestGetAllValuesForEnvironment
     */
    public function testGetAllValuesForEnvironment($environment, array $expectedValues)
    {
        $variables = $this->reader->getAllValuesForEnvironment($environment);
        $this->assertInternalType('array', $variables);
    
        $keys = array_keys($variables);
        $expectedKeys = array_keys($expectedValues);
        sort($keys);
        sort($expectedKeys);
        $this->assertSame($expectedKeys, $keys);
    
        foreach($keys as $variable)
        {
            $this->assertSame($expectedValues[$variable], $variables[$variable], "Value for $variable");
        }
    }
    
    public function providerTestGetAllValuesForEnvironment()
    {
        return array(
            array('dev', array(
                'foo' => 'foodev',
                'bar' => 'bardev',
                'baz' => null,
            )),
            array('recette', array(
                'foo' => null,
                'bar' => null,
                'baz' => 'bazrecette',
            )),
            array('prod', array(
                'foo' => 'fooprod',
                'bar' => null,
                'baz' => null,
            )),
        );
    }
    
    public function testOverrideVariable()
    {
        $environment = 'dev';
    
        $this->assertSame('foodev', $this->reader->read('foo', $environment));
        $this->assertSame('bardev', $this->reader->read('bar', $environment));
    
        $this->reader->overrideVariable('foo', 'foofoo');
    
        $this->assertSame('foofoo', $this->reader->read('foo', $environment));
        $this->assertSame('bardev', $this->reader->read('bar', $environment));
        
        $this->reader->overrideVariable('bar', null);
    
        $this->assertSame('foofoo', $this->reader->read('foo', $environment));
        $this->assertSame(null, $this->reader->read('bar', $environment));
    }
}