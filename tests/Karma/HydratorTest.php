<?php

declare(strict_types = 1);

namespace Karma;

use Gaufrette\Filesystem;
use Gaufrette\Adapter\InMemory;
use Karma\Configuration\InMemoryReader;
use Karma\FormatterProviders\NullProvider;
use Karma\FormatterProviders\CallbackProvider;
use Karma\Formatters\Rules;
use PHPUnit\Framework\TestCase;

class HydratorTest extends TestCase
{
    private
        $sourceFs,
        $targetFs,
        $reader,
        $hydrator;

    protected function setUp()
    {
        $this->sourceFs = new Filesystem(new InMemory());
        $this->targetFs = new Filesystem(new InMemory());
        $this->reader = new InMemoryReader(array(
            'var:dev' => 42,
            'var:preprod' => 51,
            'var:prod' => 69,
            'db.user:dev' => 'root',
            'db.user:preprod' => 'someUser',
            'bool:dev' => true,
            'bool:prod' => false,
            'list:dev' => array('str', 2, true, null),
            'list:prod' => array(42),
            'todo:dev' => '__TODO__',
            'fixme:dev' => '__FIXME__',
        ));

        $this->hydrator = new Hydrator($this->sourceFs, $this->targetFs, $this->reader, new Finder($this->sourceFs), new NullProvider());
        $this->hydrator->setSuffix('-dist');
    }

    /**
     * @dataProvider providerTestSimple
     */
    public function testSimple($environment, $expectedBValue, $expectedFValue)
    {
        $this->write('a.php');
        $this->write('b.php-dist', '<%var%>');
        $this->write('c.php', '<%var%>');
        $this->write('d.php-dist', 'var');
        $this->write('e.php-dist', '<%var %>');
        $this->write('f.php-dist', '<%db.user%>');
        $this->write('dir-dist/yolo.php', 'raw content');

        $this->hydrator->hydrate($environment);

        $this->assertTargetHasNot('a.php');
        $this->assertTargetHasNot('c.php');

        $this->assertTargetHas('b.php');
        $this->assertTargetHas('d.php');
        $this->assertTargetHas('e.php');
        $this->assertTargetHas('f.php');

        $this->assertTargetContent($expectedBValue, 'b.php');

        $this->assertSourceContent('<%var%>', 'c.php');
        
        $this->assertTargetContent('var', 'd.php');
        $this->assertTargetContent('<%var %>', 'e.php');
        $this->assertTargetContent($expectedFValue, 'f.php');
    }
    
    /**
     * @dataProvider providerTestSimple
     */
    public function testSimpleWithoutTarget($environment, $expectedBValue, $expectedFValue)
    {
        $this->targetFs = $this->sourceFs;
        $hydrator = new Hydrator($this->sourceFs, $this->sourceFs, $this->reader, new Finder($this->sourceFs), new NullProvider());
        $hydrator->setSuffix('-dist');
        
        $this->write('a.php');
        $this->write('b.php-dist', '<%var%>');
        $this->write('c.php', '<%var%>');
        $this->write('d.php-dist', 'var');
        $this->write('d.php', 'oldvalue');
        $this->write('e.php-dist', '<%var %>');
        $this->write('f.php-dist', '<%db.user%>');
        $this->write('f.php', 'old value');
        $this->write('dir-dist/yolo.php', 'raw content');

        $hydrator->hydrate($environment);

        $this->assertTargetHas('a.php');
        $this->assertTargetHas('b.php');
        $this->assertTargetHas('c.php');
        $this->assertTargetHas('d.php');
        $this->assertTargetHas('e.php');
        $this->assertTargetHas('f.php');

        $this->assertTargetContent($expectedBValue, 'b.php');
        $this->assertTargetContent('<%var%>', 'c.php');
        $this->assertTargetContent('var', 'd.php');
        $this->assertTargetContent('<%var %>', 'e.php');
        $this->assertTargetContent($expectedFValue, 'f.php');
    }

    public function providerTestSimple()
    {
        return array(
            array('dev', '42', 'root'),
            array('preprod', '51', 'someUser'),
        );
    }
    
    private function assertTargetHas($filename, $message = '')
    {
        if(empty($message))
        {
            $message = "$filename should exist in target FS";
        }
        
        $this->assertTrue($this->targetFs->has($filename), $message);
    }

    private function assertTargetHasNot($filename, $message = '')
    {
        if(empty($message))
        {
            $message = "$filename should NOT exist in target FS";
        }
        
        $this->assertFalse($this->targetFs->has($filename), $message);
    }
    
    private function assertTargetContent($expectedContent, $filename, $message = '')
    {
        $this->assertSame($expectedContent, $this->targetFs->read($filename), $message);
    }
    
    private function assertSourceContent($expectedContent, $filename, $message = '')
    {
        $this->assertSame($expectedContent, $this->sourceFs->read($filename), $message);
    }
    
    private function assertTargetNbFiles($expectedCount)
    {
        $this->assertCount($expectedCount, $this->targetFs->keys());
    }

    public function testTarget()
    {
        $this->write('a.php-dist', '<%var%>');
        $this->write('b.php', '<%var%>');
        $reader = new InMemoryReader([
            'var:dev' => 'test',
        ]);

        $hydrator = new Hydrator($this->sourceFs, $this->targetFs, $reader, new Finder($this->sourceFs), new NullProvider());
        $hydrator->allowNonDistFilesOverwrite();
        $hydrator->hydrate('dev');

        $this->assertTargetHas('a.php');
        $this->assertTargetContent('test', 'a.php');

        $this->assertTargetHas('b.php');
        $this->assertTargetContent('<%var%>', 'b.php');
    }

    public function testDryRun()
    {
        $this->write('a.php');
        $this->write('b.php-dist', '<%var%>');
        $this->write('c.php', '<%var%>');

        $this->hydrator
            ->setDryRun()
            ->hydrate('dev');

        $this->assertTargetHasNot('b.php');
    }

    public function testGetUnusedVariables()
    {
        $this->write('a.php');
        $this->write('b.php-dist', '<%var%>');
        $this->write('c.php-dist', '<%list%>');

        $this->hydrator
            ->hydrate('dev');

        $unusedVariables = $this->hydrator->getUnusedVariables();

        $this->assertContains('db.user', $unusedVariables);
        $this->assertContains('bool', $unusedVariables);
        $this->assertContains('todo', $unusedVariables);
        $this->assertContains('fixme', $unusedVariables);
        $this->assertNotContains('var', $unusedVariables);
        $this->assertNotContains('list', $unusedVariables);
        $this->assertCount(4, $unusedVariables);
    }

    public function testTrappedFilenames()
    {
        $existingFiles = array(
            'a.php', 'b.php-dist', 'c.php-dis', 'd.php-distt', 'e.php-dist.dist', 'f.dist', 'g-dist.php', 'h.php-dist-dist'
        );

        foreach($existingFiles as $file)
        {
            $this->write($file);
        }

        $this->hydrator->hydrate('prod');

        $createdFiles = array('b.php', 'h.php-dist');
        $allFiles = array_merge($existingFiles, $createdFiles);

        // check there is no extra generated file
        $this->assertTargetNbFiles(count($createdFiles));

        foreach($createdFiles as $file)
        {
            $this->assertTargetHas($file, "File $file should be created");
        }
        
        foreach($existingFiles as $file)
        {
            $this->assertTargetHasNot($file, "File $file should'nt be overwritten");
        }
    }
    
    public function testTrappedFilenamesToTarget()
    {
        $existingFiles = array(
            'a.php', 'b.php-dist', 'c.php-dis', 'd.php-distt', 'e.php-dist.dist', 'f.dist', 'g-dist.php', 'h.php-dist-dist', 'dist-dir/z-dist'
        );
    
        foreach($existingFiles as $file)
        {
            $this->write($file);
        }
    
        $this->hydrator
            ->allowNonDistFilesOverwrite()
            ->hydrate('prod');
    
        $expectedFiles = array(
            'b.php', 'h.php-dist', 'a.php', 'c.php-dis', 'd.php-distt', 'e.php-dist.dist', 'f.dist', 'g-dist.php', 'z'
        );
        
        // check there is no extra generated file
        $this->assertTargetNbFiles(count($expectedFiles));

        foreach($expectedFiles as $file)
        {
            $this->assertTargetHas($file, "File $file should be created");
        }
    }
    
    /**
     * @expectedException \RuntimeException
     */
    public function testDuplicatedFilenamesToTarget()
    {
        $existingFiles = array('dist-1/test.php-dist', 'dist-2/test.php-dist');

        foreach($existingFiles as $file)
        {
            $this->write($file);
        }

        $this->hydrator
            ->allowNonDistFilesOverwrite()
            ->hydrate('prod');
    }

    public function testIdempotentHydratationToTarget()
    {
        $this->targetFs->write('test.php', 'oldValue');
        $existingFiles = array('dist/test.php-dist', 'dist/test2.php-dist');

        foreach($existingFiles as $file)
        {
            $this->write($file, 'newValue');
        }

        $this->hydrator
            ->allowNonDistFilesOverwrite()
            ->hydrate('prod');

        // check there is no extra generated file
        $expectedFiles = array('test.php', 'test2.php');
        $this->assertTargetNbFiles(count($expectedFiles));

        foreach($expectedFiles as $file)
        {
            $this->assertTargetHas($file, "File $file should be created");
        }
        
        $this->assertTargetContent('newValue', 'test.php', "File test.php shoud have been updated");
    }

    private function write($name, $content = null)
    {
        $this->sourceFs->write($name, $content);
    }

    public function testBackupFiles()
    {
        $this->write('a.php-dist');
        $this->write('b.php-dist', '<%var%>');
        $this->targetFs->write('b.php', 'oldValue');
        $this->write('c.php-dist');

        $this->hydrator
            ->enableBackup()
            ->hydrate('dev');

        $this->assertTargetHas('a.php');
        $this->assertTargetHasNot('a.php~');

        $this->assertTargetHas('b.php');
        $this->assertTargetHas('b.php~');

        $this->assertTargetHas('c.php');
        $this->assertTargetHasNot('c.php~');

        $this->assertTargetContent('42', 'b.php');
        $this->assertTargetContent('oldValue', 'b.php~');

        $this->hydrator->hydrate('dev');

        $this->assertTargetHas('a.php~');
        $this->assertTargetHas('b.php~');
        $this->assertTargetHas('c.php~');

        $this->assertTargetContent('42', 'b.php~');
    }

    public function testFormatter()
    {
        $yellFormatter = new Rules(array(
            '<true>' => 'TRUE',
            '<false>' => 'FALSE',
        ));

        $otherFormatter = new Rules(array(
            '<true>' => 'string_true',
            '<false>' => 0,
        ));

        $provider = new CallbackProvider(function ($fileExtension, ?string $index) use($yellFormatter, $otherFormatter) {
            return strtolower($index ?? '') === 'yell' ? $yellFormatter : $otherFormatter;
        });

        $this->hydrator->setFormatterProvider($provider);

        $this->write('a-dist', '<%bool%>');
        $this->write('b-dist', "<% karma:formatter = yell %>\n<%bool%>");
        $this->write('list-dist', "<%list%>\n<%karma:formatter=YeLl     %>   \n");

        $this->hydrator->hydrate('dev');
        $this->assertTargetContent('string_true', 'a');
        $this->assertTargetContent('TRUE', 'b');
        $this->assertTargetContent(implode("\n", array("str", 2, "TRUE", null)). "\n", 'list');

        $this->hydrator->hydrate('prod');
        $this->assertTargetContent('0', 'a');
        $this->assertTargetContent('FALSE', 'b');
    }

    public function testFormatterByFileExtension()
    {
        $yellFormatter = new Rules(array(
            '<true>' => 'TRUE',
        ));

        $stringFormatter = new Rules(array(
            '<true>' => 'string_true',
        ));

        $intFormatter = new Rules(array(
            '<true>' => 1,
        ));

        $provider = new CallbackProvider(function ($fileExtension, $index) use($yellFormatter, $stringFormatter, $intFormatter) {

            if($index === 'int')
            {
                return $intFormatter;
            }

            $formatters = array(
                'ini' => $intFormatter,
                'yml' => $yellFormatter,
                'txt' => $stringFormatter
            );

            return isset($formatters[$fileExtension]) ? $formatters[$fileExtension] : /* default */ $yellFormatter;
        });

        $this->hydrator->setFormatterProvider($provider);

        $this->write('a.ini-dist', '<%bool%>');
        $this->write('b.yml-dist', '<%bool%>');
        $this->write('c.txt-dist', '<%bool%>');
        $this->write('d.cfg-dist', '<%bool%>');
        $this->write('e.yml-dist', "<% karma:formatter = int %>\n<%bool%>");

        $this->hydrator->hydrate('dev');
        $this->assertTargetContent('1', 'a.ini');
        $this->assertTargetContent('TRUE', 'b.yml');
        $this->assertTargetContent('string_true', 'c.txt');
        $this->assertTargetContent('TRUE', 'd.cfg'); // default
        $this->assertTargetContent('1', 'e.yml');
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testFormatterError()
    {
        $this->write('a-dist', <<< FILE
<% karma:formatter = a %>
<% karma:formatter = b %>
FILE
        );

        $this->hydrator->hydrate('dev');
    }

    public function testTodo()
    {
        $this->write('a-dist', <<< FILE
<%todo%>
FILE
        );

        $this->hydrator->hydrate('dev');
        $unvaluedVariables = $this->hydrator->getUnvaluedVariables();

        $this->assertCount(1, $unvaluedVariables);
        $this->assertContains('todo', $unvaluedVariables);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testFixMe()
    {
        $this->write('a-dist', <<< FILE
<%fixme%>
FILE
        );

        $this->hydrator->hydrate('dev');
    }

    /**
     * @dataProvider providerTestList
     */
    public function testList($env, $expected)
    {
        $reader = new InMemoryReader(array(
            'var:dev' => array(42, 51, 69, 'some string'),
            'var:staging' => array(33),
            'var:prod' => 1337,
        ));

        $this->hydrator = new Hydrator($this->sourceFs, $this->targetFs, $reader, new Finder($this->sourceFs));

        $this->write('a.yml-dist', <<< YAML
array:
  - <%var%>
YAML
        );

        $this->hydrator->hydrate($env);
        $this->assertTargetContent($expected, 'a.yml');
    }

    public function providerTestList()
    {
        return array(
            array('dev', <<< YAML
array:
  - 42
  - 51
  - 69
  - some string
YAML
          ),
            array('staging', <<< YAML
array:
  - 33
YAML
          ),
            array('prod', <<< YAML
array:
  - 1337
YAML
          ),
        );
    }

    public function testListMultiFormat()
    {
        $reader = new InMemoryReader(array(
            'var:dev' => array(42, 51, 69),
        ));

        $this->hydrator = new Hydrator($this->sourceFs, $this->targetFs, $reader, new Finder($this->sourceFs));

        $this->write('a.php-dist', <<< PHP
\$var = array(
    <%var%>,
);
PHP
        );
        $expectedPhp = <<< PHP
\$var = array(
    42,
    51,
    69,
);
PHP;

        $this->write('b.ini-dist', <<< INI
[group]
list[]=<%var%>
INI
        );

        $expectedIni = <<< INI
[group]
list[]=42
list[]=51
list[]=69
INI;

        $this->hydrator->hydrate('dev');
        $this->assertTargetContent($expectedPhp, 'a.php');
        $this->assertTargetContent($expectedIni, 'b.ini');
    }

    public function testListEdgeCases()
    {
        $reader = new InMemoryReader(array(
            'var:dev' => array(42, 51),
            'foo:dev' => 33,
            'bar:dev' => array(1337, 1001),
        ));

        $this->hydrator = new Hydrator($this->sourceFs, $this->targetFs, $reader, new Finder($this->sourceFs));

        $this->write('a.txt-dist', <<< TXT
foo = <%var%> <%foo%>
bar[<%bar%>] = <%var%>
baz = <%bar%> <%bar%> <%bar%>
TXT
        );

        $expected = <<< TXT
foo = 42 33
foo = 51 33
bar[1337] = 42
bar[1337] = 51
bar[1001] = 42
bar[1001] = 51
baz = 1337 1337 1337
baz = 1337 1337 1001
baz = 1337 1001 1337
baz = 1337 1001 1001
baz = 1001 1337 1337
baz = 1001 1337 1001
baz = 1001 1001 1337
baz = 1001 1001 1001
TXT;

        $this->hydrator->hydrate('dev');
        $this->assertTargetContent($expected, 'a.txt');
    }

    /**
     * @dataProvider providerTestListEndOfLine
     */
    public function testListEndOfLine($content, $expected)
    {
        $reader = new InMemoryReader(array(
            'var:dev' => array(42, 51),
        ));

        $this->hydrator = new Hydrator($this->sourceFs, $this->targetFs, $reader, new Finder($this->sourceFs));

        $this->write('a.txt-dist', $content);

        $this->hydrator->hydrate('dev');
        $this->assertTargetContent($expected, 'a.txt');
    }

    public function providerTestListEndOfLine()
    {
        return array(
            "unix"    => array("line:\n - var=<%var%>\nend", "line:\n - var=42\n - var=51\nend"),
            "windows" => array("line:\r\n - var=<%var%>\r\nend", "line:\r\n - var=42\r\n - var=51\r\nend"),
            "mac" => array("line:\r - var=<%var%>\rend", "line:\r - var=42\r - var=51\rend"),
        );
    }

    /**
     * @dataProvider providerTestListDirective
     */
    public function testListDirective($content, $env, $expected)
    {
        $reader = new InMemoryReader(array(
            'items:dev' => array(42, 51, 69, 'someString'),
            'items:staging' => array(33),
            'items:prod' => 1337,
            'servers:dev' => null,
            'servers:staging' => array(),
            'servers:prod' => array('a', 'b', 'c'),
        ));

        $this->hydrator = new Hydrator($this->sourceFs, $this->targetFs, $reader, new Finder($this->sourceFs));

        $this->write('a-dist', $content);

        $this->hydrator->hydrate($env);
        $this->assertTargetContent($expected, 'a');
    }

    public function providerTestListDirective()
    {
        // nominal case
        $contentA = 'items = array( <% karma:list var=items delimiter=", " %> );';
        // alternative delimiter, some useless spaces
        $contentB = 'items: <% karma:list    var=items   delimiter="-" %>';
        // directive case, no space around tags
        $contentC = 'items: <%KaRmA:LiST var=items%>';
        // directive parameter case, another variable
        $contentD = 'servers[<% karma:list VAR=servers     delimiter="," %>]';
        // empty delimiter
        $contentE = 'servers[<% karma:list var=servers delimiter="" %>]';
        // simple char wrapper
        $contentW = 'servers: <% karma:list var=servers delimiter=", " wrapper="{":"}" %>';
        // more complex wrapper
        $contentX = 'servers: <% karma:list var=servers delimiter="</val><val>" wrapper="<values><val>":"</val></values>" %>';
        // prefix only wrapper
        $contentY = 'servers: <% karma:list var=servers delimiter="/" wrapper="arr/":"" %>';

        return array(
            array(
                $contentA, 'dev',
                "items = array( 42, 51, 69, someString );"
            ),
            array(
                $contentA, 'staging',
                "items = array( 33 );"
            ),
            array(
                $contentA, 'prod',
                "items = array( 1337 );"
            ),

            array(
                $contentB, 'dev',
                "items: 42-51-69-someString"
            ),
            array(
                $contentB, 'staging',
                "items: 33"
            ),
            array(
                $contentB, 'prod',
                "items: 1337"
            ),

            array(
                $contentC, 'dev',
                "items: 425169someString"
            ),
            array(
                $contentC, 'staging',
                "items: 33"
            ),
            array(
                $contentC, 'prod',
                "items: 1337"
            ),

            array(
                $contentD, 'dev',
                "servers[]"
            ),

            array(
                $contentD, 'staging',
                "servers[]"
            ),

            array(
                $contentD, 'prod',
                "servers[a,b,c]"
            ),

            array(
                $contentE, 'dev',
                "servers[]"
            ),

            array(
                $contentE, 'staging',
                "servers[]"
            ),

            array(
                $contentE, 'prod',
                "servers[abc]"
            ),

            array(
                $contentW, 'dev',
                "servers: "
            ),

            array(
                $contentW, 'staging',
                "servers: "
            ),

            array(
                $contentW, 'prod',
                "servers: {a, b, c}"
            ),

            array(
                $contentX, 'dev',
                "servers: "
            ),

            array(
                $contentX, 'staging',
                "servers: "
            ),

            array(
                $contentX, 'prod',
                "servers: <values><val>a</val><val>b</val><val>c</val></values>"
            ),

            array(
                $contentY, 'dev',
                "servers: "
            ),

            array(
                $contentY, 'staging',
                "servers: "
            ),

            array(
                $contentY, 'prod',
                "servers: arr/a/b/c"
            ),
        );
    }

    /**
     * @dataProvider providerTestListDirectiveSyntaxError
     * @expectedException \RuntimeException
     */
    public function testListDirectiveSyntaxError($content)
    {
        $this->write('a-dist', $content);
        $this->hydrator->hydrate('dev');
    }

    public function providerTestListDirectiveSyntaxError()
    {
        return array(
            'missing var' => array('<% karma:list %>'),
            'empty var' => array('<% karma:list var= %>'),
            'empty delimiter' => array('<% karma:list var=db.user delimiter= %>'),
            'space around equal #1' => array('<% karma:list var= db.user %>'),
            'space around equal #2' => array('<% karma:list var =db.user %>'),
            'space around equal #3' => array('<% karma:list var = db.user %>'),
            'not existing variable' => array('<% karma:list var=doesnotexist %>'),
            'disallowed spaces' => array('<% karma : list var=db.user%>'),
            'unknown parameter' => array('<% karma:list var=db.user foobar=3 %>'),
            'mispelled parameter' => array('<% karma:list var=db.user delimiterssss="," %>'),
            'wrong order #1' => array('<% var=db.user karma:list %>'),
            'wrong order #2' => array('<% karma:list delimiter=", " var=db.user %>'),
            'wrong order #3' => array('<% karma:list var=db.user wrapper="<":">" delimiter=", " %>'),
            'wrong order #4' => array('<% karma:list wrapper="<":">" var=db.user delimiter=", " %>'),
            'wrong directive' => array('<% karma:listing var=db.user %>'),
            'delimiter without quotes' => array('<% karma:list var=db.user delimiter=- %>'),
            'wrapper without quotes' => array('<% karma:list var=db.user delimiter=- wrapper=<:> %>'),
            'wrapper without both values' => array('<% karma:list var=db.user delimiter=- wrapper="<" %>'),
        );
    }

    public function testMultipleListDirective()
    {
        $reader = new InMemoryReader(array(
            'items:dev' => array(42, 51, 69, 'someString'),
            'servers:dev' => array('a', 'b', 'c'),
        ));

        $this->hydrator = new Hydrator($this->sourceFs, $this->targetFs, $reader, new Finder($this->sourceFs));
        $this->hydrator->setFormatterProvider(new CallbackProvider(function() {
            return new Rules(array('<string>' => '"<string>"'));
        }));

        $this->write('a-dist', <<<FILE
<% karma:list var=items delimiter="@" %>
<% karma:list var=servers delimiter="_" %>
FILE
        );

        $this->hydrator->hydrate('dev');
        $this->assertTargetContent( <<< FILE
42@51@69@"someString"
"a"_"b"_"c"
FILE
        , 'a');
    }

    public function testDashesInVariableNameAreAllowed()
    {
        $reader = new InMemoryReader(array(
            'var-with-dashes:dev' => 'poney',
            'dash-dash-dash:dev' => 'licorne',
        ));

        $this->hydrator = new Hydrator($this->sourceFs, $this->targetFs, $reader, new Finder($this->sourceFs));

        $this->write('a-dist', '<%var-with-dashes%> = <%dash-dash-dash%>');

        $this->hydrator->hydrate('dev');
        $this->assertTargetContent('poney = licorne', 'a');
    }

    /**
     * @dataProvider providerTestHydrateWithADifferentSystemEnvironment
     */
    public function testHydrateWithADifferentSystemEnvironment($env, $systemEnv, $expectedA, $expectedList, $expectedDirective)
    {
        $reader = new InMemoryReader(array(
            'poney:dev' => 'poney_d',
            'poney:staging' => 'poney_s',
            '@licorne:dev' => 'licorne_d',
            '@licorne:staging' => 'licorne_s',
            'env:dev' => array('d', 'e', 'v'),
            'env:staging' => array('s', 't', 'a'),
            '@dsn:dev' => array('d', 'e', 'v'),
            '@dsn:staging' => array('s', 't', 'a'),
        ));

        $this->hydrator = new Hydrator($this->sourceFs, $this->targetFs, $reader, new Finder($this->sourceFs));
        
        $this->write('a-dist', '<%poney%> = <%licorne%>');
        $this->write('list-dist', "<%dsn%>\n<%env%>");
        $this->write('directive-dist', '<% karma:list var=dsn delimiter="" %> = <% karma:list var=env delimiter="" %>');

        $this->hydrator
            ->setSystemEnvironment($systemEnv)
            ->hydrate($env);

        $this->assertTargetContent($expectedA, 'a');
        $this->assertTargetContent($expectedList, 'list');
        $this->assertTargetContent($expectedDirective, 'directive');
    }

    public function providerTestHydrateWithADifferentSystemEnvironment()
    {
        $dev = "d\ne\nv";
        $sta = "s\nt\na";

        return array(
            array('dev', 'dev', 'poney_d = licorne_d', "$dev\n$dev", 'dev = dev'),
            array('dev', 'staging', 'poney_d = licorne_s', "$sta\n$dev", 'sta = dev'),
            array('staging', 'dev', 'poney_s = licorne_d', "$dev\n$sta", 'dev = sta'),
            array('staging', 'staging', 'poney_s = licorne_s', "$sta\n$sta", 'sta = sta'),
        );
    }
    
    /**
     * @expectedException \RuntimeException
     * @group nested
     */
    public function testNestedVariables()
    {
        $reader = new InMemoryReader(array(
            'meat:dev' => 'pony',
            'burger:dev' => "<%meat%> with sparkles",
            'customer:dev' => 'prudman',
        ));

        $hydrator = new Hydrator($this->sourceFs, $this->targetFs, $reader, new Finder($this->sourceFs));
        
        $this->write('a-dist', "<%burger%>");
        $hydrator->hydrate('dev');
    }
}
