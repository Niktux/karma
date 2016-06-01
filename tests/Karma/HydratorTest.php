<?php

namespace Karma;

use Gaufrette\Filesystem;
use Gaufrette\Adapter\InMemory;
use Karma\Configuration\InMemoryReader;
use Karma\FormatterProviders\NullProvider;
use Karma\FormatterProviders\CallbackProvider;
use Karma\Formatters\Rules;

class HydratorTest extends \PHPUnit_Framework_TestCase
{
    private
        $sourceFs,
        $targetFs,
        $hydrator;

    protected function setUp()
    {
        $this->sourceFs = new Filesystem(new InMemory());
        $this->targetFs = new Filesystem(new InMemory());
        $reader = new InMemoryReader(array(
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

        $this->hydrator = new Hydrator($this->sourceFs, $this->targetFs, $reader, new Finder($this->sourceFs), new NullProvider());
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

        $this->assertTrue($this->targetFs->has('b.php'));
        $this->assertFalse($this->targetFs->has('c.php'));
        $this->assertTrue($this->targetFs->has('d.php'));
        $this->assertTrue($this->targetFs->has('e.php'));
        $this->assertTrue($this->targetFs->has('f.php'));

        $this->assertSame($expectedBValue, $this->targetFs->read('b.php'));

        $this->assertSame('<%var%>', $this->sourceFs->read('c.php'));
        
        $this->assertSame('var', $this->targetFs->read('d.php'));
        $this->assertSame('<%var %>', $this->targetFs->read('e.php'));
        $this->assertSame($expectedFValue, $this->targetFs->read('f.php'));
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

        $this->assertTrue($this->targetFs->has('a.php'));
        $this->assertSame('test', $this->targetFs->read('a.php'));
        $this->assertTrue($this->targetFs->has('b.php'));
        $this->assertSame('test', $this->targetFs->read('b.php'));
    }

    public function providerTestSimple()
    {
        return array(
            array('dev', '42', 'root'),
            array('preprod', '51', 'someUser'),
        );
    }

    public function testDryRun()
    {
        $this->write('a.php');
        $this->write('b.php-dist', '<%var%>');
        $this->write('c.php', '<%var%>');

        $this->hydrator
            ->setDryRun()
            ->hydrate('dev');

        $this->assertFalse($this->targetFs->has('b.php'));
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
        $existingFiles = array('a.php', 'b.php-dist', 'c.php-dis', 'd.php-distt', 'e.php-dist.dist', 'f.dist', 'g-dist.php', 'h.php-dist-dist');

        foreach($existingFiles as $file)
        {
            $this->write($file);
        }

        $this->hydrator->hydrate('prod');

        $createdFiles = array('b.php', 'h.php-dist');
        $allFiles = array_merge($existingFiles, $createdFiles);

        // check there is no extra generated file
        $this->assertSame(count($createdFiles), count($this->targetFs->keys()));

        foreach($createdFiles as $file)
        {
            $this->assertTrue($this->targetFs->has($file), "File $file should be created");
        }
        
        foreach($existingFiles as $file)
        {
            $this->assertFalse($this->targetFs->has($file), "File $file should'nt have been overwritted");
        }
    }
    
    public function testTrappedFilenamesToTarget()
    {
        $existingFiles = array('a.php', 'b.php-dist', 'c.php-dis', 'd.php-distt', 'e.php-dist.dist', 'f.dist', 'g-dist.php', 'h.php-dist-dist');
    
        foreach($existingFiles as $file)
        {
            $this->write($file);
        }
    
        $this->hydrator
            ->allowNonDistFilesOverwrite()
            ->hydrate('prod');
    
        $expectedFiles = array('b.php', 'h.php-dist', 'a.php', 'c.php-dis', 'd.php-distt', 'e.php-dist.dist', 'f.dist', 'g-dist.php');
        
        // check there is no extra generated file
        $this->assertSame(count($expectedFiles), count($this->targetFs->keys()));

        foreach($expectedFiles as $file)
        {
            $this->assertTrue($this->targetFs->has($file), "File $file should be created");
        }
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

        $this->assertTrue($this->targetFs->has('a.php'));
        $this->assertFalse($this->targetFs->has('a.php~'));

        $this->assertTrue($this->targetFs->has('b.php'));
        $this->assertTrue($this->targetFs->has('b.php~'));

        $this->assertTrue($this->targetFs->has('c.php'));
        $this->assertFalse($this->targetFs->has('c.php~'));

        $this->assertSame('42', $this->targetFs->read('b.php'));
        $this->assertSame('oldValue', $this->targetFs->read('b.php~'));

        $this->hydrator->hydrate('dev');

        $this->assertTrue($this->targetFs->has('a.php~'));
        $this->assertTrue($this->targetFs->has('b.php~'));
        $this->assertTrue($this->targetFs->has('c.php~'));

        $this->assertSame('42', $this->targetFs->read('b.php~'));
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

        $provider = new CallbackProvider(function ($fileExtension, $index) use($yellFormatter, $otherFormatter) {
            return strtolower($index) === 'yell' ? $yellFormatter : $otherFormatter;
        });

        $this->hydrator->setFormatterProvider($provider);

        $this->write('a-dist', '<%bool%>');
        $this->write('b-dist', "<% karma:formatter = yell %>\n<%bool%>");
        $this->write('list-dist', "<%list%>\n<%karma:formatter=YeLl     %>   \n");

        $this->hydrator->hydrate('dev');
        $this->assertSame('string_true', $this->targetFs->read('a'));
        $this->assertSame('TRUE', $this->targetFs->read('b'));
        $this->assertSame(implode("\n", array("str", 2, "TRUE", null)). "\n", $this->targetFs->read('list'));

        $this->hydrator->hydrate('prod');
        $this->assertSame('0', $this->targetFs->read('a'));
        $this->assertSame('FALSE', $this->targetFs->read('b'));
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
        $this->assertSame('1', $this->targetFs->read('a.ini'));
        $this->assertSame('TRUE', $this->targetFs->read('b.yml'));
        $this->assertSame('string_true', $this->targetFs->read('c.txt'));
        $this->assertSame('TRUE', $this->targetFs->read('d.cfg')); // default
        $this->assertSame('1', $this->targetFs->read('e.yml'));
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
        $this->assertSame($expected, $this->targetFs->read('a.yml'));
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
        $this->assertSame($expectedPhp, $this->targetFs->read('a.php'));
        $this->assertSame($expectedIni, $this->targetFs->read('b.ini'));
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
        $this->assertSame($expected, $this->targetFs->read('a.txt'));
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
        $this->assertSame($expected, $this->targetFs->read('a.txt'));
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
        $this->assertSame($expected, $this->targetFs->read('a'));
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
        $this->assertSame( <<< FILE
42@51@69@"someString"
"a"_"b"_"c"
FILE
        , $this->targetFs->read('a'));
    }

    public function testDashesInVariableNameAreAllowed()
    {
        $reader = new InMemoryReader(array(
            'var-with-dashes:dev' => 'poney',
            'dash-dash-dash:dev' => 'licorne',
        ));

        $this->hydrator = new Hydrator($this->sourceFs, $this->targetFs, $reader, new Finder($this->sourceFs));

        $this->sourceFs->write('a-dist', '<%var-with-dashes%> = <%dash-dash-dash%>');

        $this->hydrator->hydrate('dev');
        $this->assertSame('poney = licorne', $this->targetFs->read('a'));
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
        $this->sourceFs->write('a-dist', '<%poney%> = <%licorne%>');
        $this->sourceFs->write('list-dist', "<%dsn%>\n<%env%>");
        $this->sourceFs->write('directive-dist', '<% karma:list var=dsn delimiter="" %> = <% karma:list var=env delimiter="" %>');

        $this->hydrator
            ->setSystemEnvironment($systemEnv)
            ->hydrate($env);

        $this->assertSame($expectedA, $this->targetFs->read('a'));
        $this->assertSame($expectedList, $this->targetFs->read('list'));
        $this->assertSame($expectedDirective, $this->targetFs->read('directive'));
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
}
