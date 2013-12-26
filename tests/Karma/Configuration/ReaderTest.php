<?php

use Karma\Configuration\Reader;
use Karma\Configuration;
use Karma\Configuration\Parser;
use Gaufrette\Filesystem;
use Gaufrette\Adapter\InMemory;

require_once __DIR__ . '/ParserTestCase.php';

class ReaderTest extends ParserTestCase
{
    private
        $reader;
    
    protected function setUp()
    {
        parent::setUp();
        
        $variables = $this->parser->parse(self::MASTERFILE_PATH);
        $this->reader = new Reader($variables, $this->parser->getExternalVariables());
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
            array('tva', 'preprod', 20.5),
            array('tva', 'default', 19.6),

            array('apiKey', 'dev', '=2'),
            array('apiKey', 'recette', ''),
            array('apiKey', 'prod', 'qd4qs64d6q6=fgh4f6ùftgg==sdr'),
            array('apiKey', 'default', 'qd4qs64d6q6=fgh4f6ùftgg==sdr'),
            
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
                'print_errors' => true,
                'debug' => true,
                'gourdin' => 2,
                'server' => Configuration::NOT_FOUND,
                'tva' => 19.0,
                'apiKey' => '=2',
                'my.var.with.subnames' => 21,
                'user' => 'root'
            )),
            array('prod', array(
                'print_errors' => false,
                'debug' => false,
                'gourdin' => 0,
                'server' => 'sql21',
                'tva' => 19.6,
                'apiKey' => 'qd4qs64d6q6=fgh4f6ùftgg==sdr',
                'my.var.with.subnames' => 21,
                'user' => 'root'
            )),                                 
        );
    }
    
    /**
     * @dataProvider providerTestDiff
     */
    public function testDiff($environment1, $environment2, $expectedDiff)
    {
        $diff = $this->reader->compareEnvironments($environment1, $environment2);
        
        $this->assertSame($expectedDiff, $diff);
    }
    
    public function providerTestDiff()
    {
        return array(
            array('dev', 'prod', array(
                'print_errors' => array(true, false),
                'debug' => array(true, false),
                'gourdin' => array(2, 0),
                'tva' => array(19.0, 19.6),
                'server' => array(Configuration::NOT_FOUND, 'sql21'),
                'apiKey' => array('=2', 'qd4qs64d6q6=fgh4f6ùftgg==sdr'),
            )),
            array('preprod', 'prod', array(
                'gourdin' => array(1, 0),
                'tva' => array(20.5, 19.6),
                'server' => array('prod21', 'sql21'),
            )),                        
        );
    }

    public function testExternal()
    {
        $masterContent = <<<CONFFILE
[externals]
external.conf

[variables]
db.pass:
    dev = 1234
    prod = <external>
    default = root
CONFFILE;
        
        $externalContent = <<<CONFFILE
[variables]
db.pass:
    prod = veryComplexPass
CONFFILE;
        
        $files = array(
            'master.conf' => $masterContent,
            'external.conf' => $externalContent,
        );
        
        $parser = new Parser(new Filesystem(new InMemory($files)));
        
        $variables = $parser->enableIncludeSupport()
            ->enableExternalSupport()
            ->parse(self::MASTERFILE_PATH);

        $reader = new Reader($variables, $parser->getExternalVariables());
        
        $expected = array(
            'dev' => 1234,
            'prod' => 'veryComplexPass ',
            'preprod' => 'root',
        );
        
        foreach($expected as $environment => $expectedValue)
        {
            $this->assertSame($expectedValue, $reader->read('db.pass', $environment));
        }
    }
    
    /**
     * @dataProvider providerTestExternalError
     * @expectedException \RuntimeException
     */
    public function testExternalError($contentMaster)
    {
        $parser = new Parser(new Filesystem(new InMemory(array(
            self::MASTERFILE_PATH => $contentMaster,
            'empty.conf' => ''
        ))));
    
        $parser
            ->enableIncludeSupport()
            ->enableExternalSupport();
        
        $variables = $parser->parse($masterFilePath);
        $reader = new Reader($variables, $parser->getExternalVariables());
        $reader->read('toto', 'prod');
    }
    
    public function providerTestExternalError()
    {
        return array(
            'external variable without any external file' => array(<<<CONFFILE
[variables]
toto :
    prod = <external>
CONFFILE
    
            ),
            'external variable not found in external file' => array(<<<CONFFILE
[externals]
empty.conf
    
[variables]
toto :
    prod = <external>
CONFFILE
    
            ),
        );
    }    
}