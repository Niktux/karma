<?php

use Gaufrette\Filesystem;
use Gaufrette\Adapter\InMemory;
use Karma\Configuration\Parser;

require_once __DIR__ . '/ParserTestCase.php';

class ParserTest extends ParserTestCase
{
    /**
     * @dataProvider providerTestRead
     */
    public function testRead($variable, $environment, $expectedValue)
    {
        $this->variables = $this->parser->setEOL("\n")->parse(self::MASTERFILE_PATH);
    
        $this->assertArrayHasKey($variable, $this->variables);
        $this->assertArrayHasKey('env', $this->variables[$variable]);
        $this->assertArrayHasKey($environment, $this->variables[$variable]['env']);
        $this->assertSame($expectedValue, $this->variables[$variable]['env'][$environment]);
    }
    
    public function providerTestRead()
    {
        return array(
            // master.conf
            array('print_errors', 'prod', false),    
            array('print_errors', 'preprod', false),    
            array('print_errors', 'default', true),   
             
            array('debug', 'dev', true),    
            array('debug', 'default', false),    
            
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
            array('apiKey', 'default', 'qd4qs64d6q6=fgh4f6ùftgg==sdr'),
            
            array('my.var.with.subnames', 'default', 21),
                        
            // db.conf
            array('user', 'default', 'root'),    
        );    
    }
    
    /**
     * @dataProvider providerTestSyntaxError
     * @expectedException \RuntimeException
     */
    public function testSyntaxError($contentMaster)
    {
        $this->parser = new Parser(new Filesystem(new InMemory(array(
            self::MASTERFILE_PATH => $contentMaster,
            'empty.conf' => '',
            'vicious.conf' => <<<CONFFILE
[variables]
var1:
        default= 0
[variables]
viciousDuplicatedVariable:
        default= 0
[variables]
var2:
        default= 0
            
CONFFILE
        ))));
        
        $this->parser->parse(self::MASTERFILE_PATH);
    }
    
    public function providerTestSyntaxError()
    {
        return array(
            'missing =' => array(<<<CONFFILE
[variables]
print_errors:
    default:true
CONFFILE

            ), 
            'missing variables' => array(<<<CONFFILE
print_errors:
    default:true
CONFFILE

            ), 
            'include not found' => array(<<<CONFFILE
[includes]
empty.conf
notfound.conf
CONFFILE
                      
            ),
            'variables mispelled' => array(<<<CONFFILE
[variable]
toto:
    tata = titi
CONFFILE
            
            ),
            'missing variable name' => array(<<<CONFFILE
[variables]
prod = value
CONFFILE
            
            ),
            'duplicated variables' => array(<<<CONFFILE
[variables]
toto:
    prod = tata
toto:
    dev = titi
CONFFILE
                
            ),
            'duplicated variables with some spaces before' => array(<<<CONFFILE
[variables]
 toto:
    prod = tata
toto:
    dev = titi
CONFFILE
                            
            ),
            'duplicated variables with some spaces after' => array(<<<CONFFILE
[variables]
toto :
    prod = tata
toto:
    dev = titi
CONFFILE
                            
            ),
            'duplicated variables with some spaces both' => array(<<<CONFFILE
[variables]
toto :
    prod = tata
 toto:
    dev = titi
CONFFILE
                            
            ),
            'duplicated variables in different files' => array(<<<CONFFILE
[includes]
vicious.conf
[variables]
viciousDuplicatedVariable:
    prod = tata
CONFFILE
            
            ),            
            'duplicated environment' => array(<<<CONFFILE
[variables]
toto:
    prod = tata
    preprod, recette = titi
    dev, prod, qualif = tutu
CONFFILE
            
            ),
            'missing : after variable name' => array(<<<CONFFILE
[variables]
toto
    prod = 2
CONFFILE
            
            ),
            'variable name syntax error' => array(<<<CONFFILE
[includes]
toto =
    prod = 2
CONFFILE
            
            ),
            'variable without value' => array(<<<CONFFILE
[includes]
toto :
    prod = 2
tata :
titi :
    dev = 3                
CONFFILE
            
            ),
        );
    }
}