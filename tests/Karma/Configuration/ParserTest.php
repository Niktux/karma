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
            array('gourdin', 'staging', 'string with blanks'),
                
            array('server', 'prod', 'sql21'),    
            array('server', 'preprod', 'prod21'),    
            array('server', 'recette', 'rec21'),    
            array('server', 'qualif', 'rec21'),   
             
            array('tva', 'dev', 19.0),
            array('tva', 'preprod', 20.5),
            array('tva', 'default', 19.6),

            array('apiKey', 'dev', '=2'),
            array('apiKey', 'recette', ''),
            array('apiKey', 'default', 'qd4#qs64d6q6=fgh4f6ùftgg==sdr'),
            
            array('my.var.with.subnames', 'default', 21),
                        
            array('param', 'dev', '${param}'),
            array('param', 'staging', 'Some${nested}param'),
                        
            // db.conf
            array('user', 'default', 'root'),    
            
            // lists
            array('list.ok', 'dev', array('one', 'two', 'three')),
            array('list.ok', 'staging', array('one', 'two')),
            array('list.ok', 'prod', array('alone')),
            array('list.ok', 'preprod', 'not_a_list'),
            array('list.ok', 'default', array('single value with blanks')),
            array('list.ok', 'other', array('', 2, 'third')),
            array('list.ok', 'staging2', array('')),
            array('list.ok', 'staging3', array('', '', '', '', '')),
            
            array('list.notlist', 'dev', 'string[weird'),
            array('list.notlist', 'staging', 'string]weird'),
            array('list.notlist', 'prod', '[string[weird'),
            array('list.notlist', 'default', '[string'),
            array('list.notlist', 'preprod', 'string]'),
            array('list.notlist', 'other', 'arr[]'),
            array('list.notlist', 'staging2', 'arr[tung]'),
            array('list.notlist', 'staging3', '[1,2,3]4'),
            
            array('list.notlist', 'string1', '[]]'),
            array('list.notlist', 'string2', '[[]'),
            array('list.notlist', 'string3', '[[]]'),
            array('list.notlist', 'string4', '[][]'),
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
        
        $this->parser
            ->enableIncludeSupport()
            ->enableExternalSupport()
            ->enableGroupSupport()
            ->parse(self::MASTERFILE_PATH);
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
[variables]
toto =
    prod = 2
CONFFILE
            ),
            'variable without value' => array(<<<CONFFILE
[variables]
toto :
    prod = 2
tata :
titi :
    dev = 3
CONFFILE
            ),
            'last variable without value' => array(<<<CONFFILE
[variables]
toto :
    prod = 2
tata :
    dev = 3
titi :
CONFFILE
            ),
            'invalid name format for include file' => array(<<<CONFFILE
[includes]
notADotConfFile                            
CONFFILE
            ),
            'comments not on its own line' => array(<<<CONFFILE
[variables]  # illegal comment
toto:
    foo = bar
CONFFILE
            ),
            'groups syntax error : missing [] #1' => array(<<<CONFFILE
[groups]
name = foobar
CONFFILE
            ),
            'groups syntax error : missing [] #2' => array(<<<CONFFILE
[groups]
name = [foobar
CONFFILE
            ),
            'groups syntax error : missing [] #3' => array(<<<CONFFILE
[groups]
name = foobar]
CONFFILE
            ),
            'groups syntax error : missing [] #4' => array(<<<CONFFILE
[groups]
name = [foob]ar
CONFFILE
            ),
            'groups syntax error : missing [] #5' => array(<<<CONFFILE
[groups]
name = fo[obar]
CONFFILE
            ),
            'groups syntax error : missing [] #6' => array(<<<CONFFILE
[groups]
name = fo[ob]ar
CONFFILE
            ),
            'groups syntax error : not a single list' => array(<<<CONFFILE
[groups]
name = [a,b,c][d,e,f]
CONFFILE
            ),
            'groups syntax error : empty env #1' => array(<<<CONFFILE
[groups]
name = []
CONFFILE
            ),
            'groups syntax error : empty env #2' => array(<<<CONFFILE
[groups]
name = [dev,staging,]
CONFFILE
            ),
            'groups syntax error : empty env #3' => array(<<<CONFFILE
[groups]
name = [,dev,staging]
CONFFILE
            ),
            'groups syntax error : empty env #4' => array(<<<CONFFILE
[groups]
name = [dev,,staging]
CONFFILE
            ),
            'groups syntax error : duplicated group name' => array(<<<CONFFILE
[groups]
prod = [dev,staging]
prod = [preprod]
CONFFILE
            ),
            'groups syntax error : duplicated environment in same group' => array(<<<CONFFILE
[groups]
prod = [dev,staging, dev]
CONFFILE
            ),
            'groups syntax error : circular reference' => array(<<<CONFFILE
[groups]
foo = [bar]
bar = [baz]
CONFFILE
            ),
            'groups syntax error : env in many groups' => array(<<<CONFFILE
[groups]
foo = [baz]
bar = [baz]
CONFFILE
            ),
        );
    }
    
    public function testExternal()
    {
        $masterContent = <<<CONFFILE
[externals]
external1.conf
external2.conf

[variables]
db.pass:
    dev = 1234
    prod = <external>
    default = root
db.user:
    staging = <external>
    default = root
CONFFILE;
        
        $externalContent1 = <<<CONFFILE
[variables]
db.pass:
    prod = veryComplexPass
CONFFILE;
        
        $externalContent2 = <<<CONFFILE
[variables]
db.user:
    staging = someUser
CONFFILE;
        
        $files = array(
            self::MASTERFILE_PATH => $masterContent,
            'external1.conf' => $externalContent1,
            'external2.conf' => $externalContent2,
        );
        
        $parser = new Parser(new Filesystem(new InMemory($files)));
        
        $parser->enableIncludeSupport()
            ->enableExternalSupport();

        $variables = $parser->parse(self::MASTERFILE_PATH);
        
        $expected = array(
            'db.pass' => array(
                'dev' => 1234,
                'prod' => '<external>',
                'default' => 'root',
            ),
            'db.user' => array(
                'staging' => '<external>',
                'default' => 'root',
            ),
        );
        
        foreach($expected as $variable => $info)
        {
            foreach($info as $environment => $expectedValue)
            {
                $this->assertArrayHasKey($variable, $variables);
                $this->assertArrayHasKey('env', $variables[$variable]);
                $this->assertArrayHasKey($environment, $variables[$variable]['env']);
                $this->assertSame($expectedValue, $variables[$variable]['env'][$environment]);
            }
        }
    }
    
    public function testGroups()
    {
        $masterContent = <<<CONFFILE
[groups]
# comment
qa = [ staging, preprod ]
dev = [ dev1, dev2,dev3]
 # comment
production=[prod]

[variables]
db.pass:
    dev = 1234
    qa = password
    prod = <external>
db.user:
    dev1 = devuser1
    dev2 = devuser2
    dev3 = devuser3
    qa = qauser
db.cache:
    preprod = true
    default = false
CONFFILE;
        
        $parser = new Parser(new Filesystem(new InMemory(array(self::MASTERFILE_PATH => $masterContent))));
        
        $parser->enableIncludeSupport()
            ->enableExternalSupport()
            ->enableGroupSupport();

        $variables = $parser->parse(self::MASTERFILE_PATH);
        
        $expected = array(
            'db.pass' => array(
                'dev' => 1234,
                'qa' => 'password',
                'prod' => '<external>',
            ),
            'db.user' => array(
                'dev1' => 'devuser1',
                'dev2' => 'devuser2',
                'dev3' => 'devuser3',
                'qa' => 'qauser',
            ),
            'db.cache' => array(
                'preprod' => true,
                'default' => false,
            ),
        );
        
        foreach($expected as $variable => $info)
        {
            foreach($info as $environment => $expectedValue)
            {
                $this->assertArrayHasKey($variable, $variables);
                $this->assertArrayHasKey('env', $variables[$variable]);
                $this->assertArrayHasKey($environment, $variables[$variable]['env']);
                $this->assertSame($expectedValue, $variables[$variable]['env'][$environment]);
            }
        }
        
        $groups = $parser->getGroups();
        
        $expected = array(
        	'dev' => array('dev1', 'dev2', 'dev3'),
        	'qa' => array('staging', 'preprod'),
            'production' => array('prod'),
        );
        
        ksort($groups);
        ksort($expected);
        
        $this->assertSame($expected, $groups);
    }
}