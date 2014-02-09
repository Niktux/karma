<?php

require_once __DIR__ . '/CommandTestCase.php';

class DisplayTest extends CommandTestCase
{
    /**
     * @dataProvider providerTestDisplay
     */
    public function testDisplay($env)
    {
        $this->runCommand('display', array('--env' => $env));
        
        $reader = $this->app['configuration'];
        $valueFoo = $reader->read('foo', $env);
        
        $this->assertDisplay("~Display $env values~");    
        $this->assertDisplay("~$valueFoo~");
    }
    
    public function providerTestDisplay()
    {
        return array(
            array('dev'),
            array('prod'),
        );
    }
    
    public function testValueFilter()
    {
        $env = 'dev';
    
        $reader = $this->app['configuration'];
        $valueFoo = $reader->read('foo', $env);
        $valueBar = $reader->read('bar', $env);
    
        $this->runCommand('display', array('--value' => $valueBar));
        
        // Unit Test sanity test
        $this->assertNotSame($valueFoo, $valueBar);
        
        $this->assertDisplay("~Display $env values~");
        $this->assertNotDisplay("~$valueFoo~");
        $this->assertNotDisplay("~$valueFoo~");
    }
}