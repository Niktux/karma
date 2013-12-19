<?php

use Karma\Configuration\Reader;
use Karma\Configuration;

require_once __DIR__ . '/ParserTestCase.php';

class ReaderTest extends ParserTestCase
{
    private
        $reader;
    
    protected function setUp()
    {
        parent::setUp();
        
        $this->reader = new Reader($this->parser, self::MASTERFILE_PATH);
    }
    
    public function providerTestRead()
    {
        return array(
            // master.conf
            array('print_errors', 'prod', false),    
            array('print_errors', 'preprod', false),    
            array('print_errors', 'recette', true),   
            array('print_errors', 'qualif', true),   
            array('print_errors', 'integration', true),   
            array('print_errors', 'dev', true),   
             
            array('debug', 'prod', false),    
            array('debug', 'preprod', false),    
            array('debug', 'recette', false),    
            array('debug', 'qualif', false),    
            array('debug', 'integration', false),    
            array('debug', 'dev', true),    
            
            array('gourdin', 'prod', 0),    
            array('gourdin', 'preprod', 1),    
            array('gourdin', 'recette', 1),    
            array('gourdin', 'qualif', 1),    
            array('gourdin', 'integration', null),    
            array('gourdin', 'dev', 2),
                
            array('server', 'prod', 'sql21'),    
            array('server', 'preprod', 'prod21'),    
            array('server', 'recette', 'rec21'),    
            array('server', 'qualif', 'rec21'),   
             
            array('tva', 'dev', 19.0),
            array('tva', 'default', 19.6),

            array('my.var.with.subnames', 'dev', 21),                
            array('my.var.with.subnames', 'default', 21),                
            
            // db.conf
            array('user', 'default', 'root'),    
        );    
    }
    
    /**
     * @dataProvider providerTestRead
     */
    public function testRead($variable, $environment, $expectedValue)
    {
        $this->assertSame($expectedValue, $this->reader->read($variable, $environment));
    }
    
    /**
     * @dataProvider providerTestRead
     */
    public function testReadWithDefaultEnvironment($variable, $environment, $expectedValue)
    {
        $this->reader->setDefaultEnvironment($environment);
    
        $this->assertSame($expectedValue, $this->reader->read($variable));
    }
    
    /**
     * @dataProvider providerTestReadNotFoundValue
     * @expectedException \RuntimeException
     */
    public function testReadNotFoundValue($variable, $environment)
    {
        $this->reader->read($variable, $environment);
    }
    
    public function providerTestReadNotFoundValue()
    {
        return array(
            array('thisvariabledoesnotexist', 'dev'),
            array('server', 'dev'),
        );
    }
    
    public function testGetAllVariables()
    {
        $variables = $this->reader->getAllVariables();
        sort($variables);
        
        $expected = array('print_errors', 'debug', 'gourdin', 'server', 'tva', 'apiKey', 'my.var.with.subnames', 'user');
        sort($expected);
        
        $this->assertSame($expected, $variables);
    }
}