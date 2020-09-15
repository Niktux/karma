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
    private Filesystem
        $sourceFs,
        $targetFs;
    private InMemoryReader
        $reader;
    private Hydrator
        $hydrator;

    protected function setUp(): void
    {
        $this->sourceFs = new Filesystem(new InMemory());
        $this->targetFs = new Filesystem(new InMemory());
        $this->reader = new InMemoryReader([
            'var:dev' => 42,
            'var:preprod' => 51,
            'var:prod' => 69,
            'db.user:dev' => 'root',
            'db.user:preprod' => 'someUser',
            'bool:dev' => true,
            'bool:prod' => false,
            'list:dev' => ['str', 2, true, null],
            'list:prod' => [42],
            'todo:dev' => '__TODO__',
            'fixme:dev' => '__FIXME__',
        ]);

        $this->hydrator = new Hydrator($this->sourceFs, $this->targetFs, $this->reader, new Finder($this->sourceFs), new NullProvider());
        $this->hydrator->setSuffix('-dist');
    }

    /**
     * @dataProvider providerTestSimple
     */
    public function testSimple(string $environment, string $expectedBValue, string $expectedFValue): void
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
    public function testSimpleWithoutTarget(string $environment, string  $expectedBValue, string $expectedFValue): void
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

    public function providerTestSimple(): array
    {
        return [
            ['dev', '42', 'root'],
            ['preprod', '51', 'someUser'],
        ];
    }
    
    private function assertTargetHas(string $filename, string $message = ''): void
    {
        if(empty($message))
        {
            $message = "$filename should exist in target FS";
        }
        
        self::assertTrue($this->targetFs->has($filename), $message);
    }

    private function assertTargetHasNot(string $filename, string $message = ''): void
    {
        if(empty($message))
        {
            $message = "$filename should NOT exist in target FS";
        }
        
        self::assertFalse($this->targetFs->has($filename), $message);
    }
    
    private function assertTargetContent(string $expectedContent, string $filename, string $message = ''): void
    {
        self::assertSame($expectedContent, $this->targetFs->read($filename), $message);
    }
    
    private function assertSourceContent(string $expectedContent, string $filename, string $message = ''): void
    {
        self::assertSame($expectedContent, $this->sourceFs->read($filename), $message);
    }
    
    private function assertTargetNbFiles(int $expectedCount): void
    {
        self::assertCount($expectedCount, $this->targetFs->keys());
    }

    public function testTarget(): void
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

    public function testDryRun(): void
    {
        $this->write('a.php');
        $this->write('b.php-dist', '<%var%>');
        $this->write('c.php', '<%var%>');

        $this->hydrator
            ->setDryRun()
            ->hydrate('dev');

        $this->assertTargetHasNot('b.php');
    }

    public function testGetUnusedVariables(): void
    {
        $this->write('a.php');
        $this->write('b.php-dist', '<%var%>');
        $this->write('c.php-dist', '<%list%>');

        $this->hydrator
            ->hydrate('dev');

        $unusedVariables = $this->hydrator->getUnusedVariables();

        self::assertContains('db.user', $unusedVariables);
        self::assertContains('bool', $unusedVariables);
        self::assertContains('todo', $unusedVariables);
        self::assertContains('fixme', $unusedVariables);
        self::assertNotContains('var', $unusedVariables);
        self::assertNotContains('list', $unusedVariables);
        self::assertCount(4, $unusedVariables);
    }

    public function testTrappedFilenames(): void
    {
        $existingFiles = [
            'a.php', 'b.php-dist', 'c.php-dis', 'd.php-distt', 'e.php-dist.dist', 'f.dist', 'g-dist.php', 'h.php-dist-dist'
        ];

        foreach($existingFiles as $file)
        {
            $this->write($file);
        }

        $this->hydrator->hydrate('prod');

        $createdFiles = ['b.php', 'h.php-dist'];

        // check there is no extra generated file
        $this->assertTargetNbFiles(count($createdFiles));

        foreach($createdFiles as $file)
        {
            $this->assertTargetHas($file, "File $file should be created");
        }
        
        foreach($existingFiles as $file)
        {
            $this->assertTargetHasNot($file, "File $file shouldn't be overwritten");
        }
    }
    
    public function testTrappedFilenamesToTarget(): void
    {
        $existingFiles = [
            'a.php', 'b.php-dist', 'c.php-dis', 'd.php-distt', 'e.php-dist.dist', 'f.dist', 'g-dist.php', 'h.php-dist-dist', 'dist-dir/z-dist'
        ];
    
        foreach($existingFiles as $file)
        {
            $this->write($file);
        }
    
        $this->hydrator
            ->allowNonDistFilesOverwrite()
            ->hydrate('prod');
    
        $expectedFiles = [
            'b.php', 'h.php-dist', 'a.php', 'c.php-dis', 'd.php-distt', 'e.php-dist.dist', 'f.dist', 'g-dist.php', 'z'
        ];
        
        // check there is no extra generated file
        $this->assertTargetNbFiles(count($expectedFiles));

        foreach($expectedFiles as $file)
        {
            $this->assertTargetHas($file, "File $file should be created");
        }
    }
    
    public function testDuplicatedFilenamesToTarget(): void
    {
        $this->expectException(\RuntimeException::class);

        $existingFiles = ['dist-1/test.php-dist', 'dist-2/test.php-dist'];

        foreach($existingFiles as $file)
        {
            $this->write($file);
        }

        $this->hydrator
            ->allowNonDistFilesOverwrite()
            ->hydrate('prod');
    }

    public function testIdempotentHydratationToTarget(): void
    {
        $this->targetFs->write('test.php', 'oldValue');
        $existingFiles = ['dist/test.php-dist', 'dist/test2.php-dist'];

        foreach($existingFiles as $file)
        {
            $this->write($file, 'newValue');
        }

        $this->hydrator
            ->allowNonDistFilesOverwrite()
            ->hydrate('prod');

        // check there is no extra generated file
        $expectedFiles = ['test.php', 'test2.php'];
        $this->assertTargetNbFiles(count($expectedFiles));

        foreach($expectedFiles as $file)
        {
            $this->assertTargetHas($file, "File $file should be created");
        }
        
        $this->assertTargetContent('newValue', 'test.php', "File test.php should have been updated");
    }

    private function write(string $name, ?string $content = null): void
    {
        $this->sourceFs->write($name, $content);
    }

    public function testBackupFiles(): void
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

    public function testFormatter(): void
    {
        $yellFormatter = new Rules([
            '<true>' => 'TRUE',
            '<false>' => 'FALSE',
        ]);

        $otherFormatter = new Rules([
            '<true>' => 'string_true',
            '<false>' => 0,
        ]);

        $provider = new CallbackProvider(static function ($fileExtension, ?string $index) use($yellFormatter, $otherFormatter) {
            return strtolower($index ?? '') === 'yell' ? $yellFormatter : $otherFormatter;
        });

        $this->hydrator->setFormatterProvider($provider);

        $this->write('a-dist', '<%bool%>');
        $this->write('b-dist', "<% karma:formatter = yell %>\n<%bool%>");
        $this->write('list-dist', "<%list%>\n<%karma:formatter=YeLl     %>   \n");

        $this->hydrator->hydrate('dev');
        $this->assertTargetContent('string_true', 'a');
        $this->assertTargetContent('TRUE', 'b');
        $this->assertTargetContent(implode("\n", ["str", 2, "TRUE", null]). "\n", 'list');

        $this->hydrator->hydrate('prod');
        $this->assertTargetContent('0', 'a');
        $this->assertTargetContent('FALSE', 'b');
    }

    public function testFormatterByFileExtension(): void
    {
        $yellFormatter = new Rules([
            '<true>' => 'TRUE',
        ]);

        $stringFormatter = new Rules([
            '<true>' => 'string_true',
        ]);

        $intFormatter = new Rules([
            '<true>' => 1,
        ]);

        $provider = new CallbackProvider(static function ($fileExtension, $index) use($yellFormatter, $stringFormatter, $intFormatter) {

            if($index === 'int')
            {
                return $intFormatter;
            }

            $formatters = [
                'ini' => $intFormatter,
                'yml' => $yellFormatter,
                'txt' => $stringFormatter
            ];

            return $formatters[$fileExtension] ?? /* default */ $yellFormatter;
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

    public function testFormatterError(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->write('a-dist', <<< FILE
<% karma:formatter = a %>
<% karma:formatter = b %>
FILE
        );

        $this->hydrator->hydrate('dev');
    }

    public function testTodo(): void
    {
        $this->write('a-dist', <<< FILE
<%todo%>
FILE
        );

        $this->hydrator->hydrate('dev');
        $unvaluedVariables = $this->hydrator->getUnvaluedVariables();

        self::assertCount(1, $unvaluedVariables);
        self::assertContains('todo', $unvaluedVariables);
    }

    public function testFixMe(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->write('a-dist', <<< FILE
<%fixme%>
FILE
        );

        $this->hydrator->hydrate('dev');
    }

    /**
     * @dataProvider providerTestList
     */
    public function testList(string $env, string $expected): void
    {
        $reader = new InMemoryReader([
            'var:dev' => [42, 51, 69, 'some string'],
            'var:staging' => [33],
            'var:prod' => 1337,
        ]);

        $this->hydrator = new Hydrator($this->sourceFs, $this->targetFs, $reader, new Finder($this->sourceFs));

        $this->write('a.yml-dist', <<< YAML
array:
  - <%var%>
YAML
        );

        $this->hydrator->hydrate($env);
        $this->assertTargetContent($expected, 'a.yml');
    }

    public function providerTestList(): array
    {
        return [
            ['dev', <<< YAML
array:
  - 42
  - 51
  - 69
  - some string
YAML
            ],
            ['staging', <<< YAML
array:
  - 33
YAML
            ],
            ['prod', <<< YAML
array:
  - 1337
YAML
            ],
        ];
    }

    public function testListMultiFormat(): void
    {
        $reader = new InMemoryReader([
            'var:dev' => [42, 51, 69],
        ]);

        $this->hydrator = new Hydrator($this->sourceFs, $this->targetFs, $reader, new Finder($this->sourceFs));

        $this->write('a.php-dist', <<< CONF_IN_PHP
\$var = [
    <%var%>,
];
CONF_IN_PHP
        );
        $expectedPhp = <<< CONF_IN_PHP
\$var = [
    42,
    51,
    69,
];
CONF_IN_PHP;

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

    public function testListEdgeCases(): void
    {
        $reader = new InMemoryReader([
            'var:dev' => [42, 51],
            'foo:dev' => 33,
            'bar:dev' => [1337, 1001],
        ]);

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
    public function testListEndOfLine(string $content, string $expected): void
    {
        $reader = new InMemoryReader([
            'var:dev' => [42, 51],
        ]);

        $this->hydrator = new Hydrator($this->sourceFs, $this->targetFs, $reader, new Finder($this->sourceFs));

        $this->write('a.txt-dist', $content);

        $this->hydrator->hydrate('dev');
        $this->assertTargetContent($expected, 'a.txt');
    }

    public function providerTestListEndOfLine(): array
    {
        return [
            "unix"    => ["line:\n - var=<%var%>\nend", "line:\n - var=42\n - var=51\nend"],
            "windows" => ["line:\r\n - var=<%var%>\r\nend", "line:\r\n - var=42\r\n - var=51\r\nend"],
            "mac" => ["line:\r - var=<%var%>\rend", "line:\r - var=42\r - var=51\rend"],
        ];
    }

    /**
     * @dataProvider providerTestListDirective
     */
    public function testListDirective(string $content, string $env, string $expected): void
    {
        $reader = new InMemoryReader([
            'items:dev' => [42, 51, 69, 'someString'],
            'items:staging' => [33],
            'items:prod' => 1337,
            'servers:dev' => null,
            'servers:staging' => [],
            'servers:prod' => ['a', 'b', 'c'],
        ]);

        $this->hydrator = new Hydrator($this->sourceFs, $this->targetFs, $reader, new Finder($this->sourceFs));

        $this->write('a-dist', $content);

        $this->hydrator->hydrate($env);
        $this->assertTargetContent($expected, 'a');
    }

    public function providerTestListDirective(): array
    {
        // nominal case
        $contentA = 'items = [ <% karma:list var=items delimiter=", " %> ];';
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

        return [
            [
                $contentA, 'dev',
                "items = [ 42, 51, 69, someString ];"
            ],
            [
                $contentA, 'staging',
                "items = [ 33 ];"
            ],
            [
                $contentA, 'prod',
                "items = [ 1337 ];"
            ],

            [
                $contentB, 'dev',
                "items: 42-51-69-someString"
            ],
            [
                $contentB, 'staging',
                "items: 33"
            ],
            [
                $contentB, 'prod',
                "items: 1337"
            ],

            [
                $contentC, 'dev',
                "items: 425169someString"
            ],
            [
                $contentC, 'staging',
                "items: 33"
            ],
            [
                $contentC, 'prod',
                "items: 1337"
            ],

            [
                $contentD, 'dev',
                "servers[]"
            ],

            [
                $contentD, 'staging',
                "servers[]"
            ],

            [
                $contentD, 'prod',
                "servers[a,b,c]"
            ],

            [
                $contentE, 'dev',
                "servers[]"
            ],

            [
                $contentE, 'staging',
                "servers[]"
            ],

            [
                $contentE, 'prod',
                "servers[abc]"
            ],

            [
                $contentW, 'dev',
                "servers: "
            ],

            [
                $contentW, 'staging',
                "servers: "
            ],

            [
                $contentW, 'prod',
                "servers: {a, b, c}"
            ],

            [
                $contentX, 'dev',
                "servers: "
            ],

            [
                $contentX, 'staging',
                "servers: "
            ],

            [
                $contentX, 'prod',
                "servers: <values><val>a</val><val>b</val><val>c</val></values>"
            ],

            [
                $contentY, 'dev',
                "servers: "
            ],

            [
                $contentY, 'staging',
                "servers: "
            ],

            [
                $contentY, 'prod',
                "servers: arr/a/b/c"
            ],
        ];
    }

    /**
     * @dataProvider providerTestListDirectiveSyntaxError
     */
    public function testListDirectiveSyntaxError(string $content): void
    {
        $this->expectException(\RuntimeException::class);

        $this->write('a-dist', $content);
        $this->hydrator->hydrate('dev');
    }

    public function providerTestListDirectiveSyntaxError(): array
    {
        return [
            'missing var' => ['<% karma:list %>'],
            'empty var' => ['<% karma:list var= %>'],
            'empty delimiter' => ['<% karma:list var=db.user delimiter= %>'],
            'space around equal #1' => ['<% karma:list var= db.user %>'],
            'space around equal #2' => ['<% karma:list var =db.user %>'],
            'space around equal #3' => ['<% karma:list var = db.user %>'],
            'not existing variable' => ['<% karma:list var=doesnotexist %>'],
            'disallowed spaces' => ['<% karma : list var=db.user%>'],
            'unknown parameter' => ['<% karma:list var=db.user foobar=3 %>'],
            'mispelled parameter' => ['<% karma:list var=db.user delimiterssss="," %>'],
            'wrong order #1' => ['<% var=db.user karma:list %>'],
            'wrong order #2' => ['<% karma:list delimiter=", " var=db.user %>'],
            'wrong order #3' => ['<% karma:list var=db.user wrapper="<":">" delimiter=", " %>'],
            'wrong order #4' => ['<% karma:list wrapper="<":">" var=db.user delimiter=", " %>'],
            'wrong directive' => ['<% karma:listing var=db.user %>'],
            'delimiter without quotes' => ['<% karma:list var=db.user delimiter=- %>'],
            'wrapper without quotes' => ['<% karma:list var=db.user delimiter=- wrapper=<:> %>'],
            'wrapper without both values' => ['<% karma:list var=db.user delimiter=- wrapper="<" %>'],
        ];
    }

    public function testMultipleListDirective(): void
    {
        $reader = new InMemoryReader([
            'items:dev' => [42, 51, 69, 'someString'],
            'servers:dev' => ['a', 'b', 'c'],
        ]);

        $this->hydrator = new Hydrator($this->sourceFs, $this->targetFs, $reader, new Finder($this->sourceFs));
        $this->hydrator->setFormatterProvider(new CallbackProvider(static function() {
            return new Rules(['<string>' => '"<string>"']);
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

    public function testDashesInVariableNameAreAllowed(): void
    {
        $reader = new InMemoryReader([
            'var-with-dashes:dev' => 'poney',
            'dash-dash-dash:dev' => 'licorne',
        ]);

        $this->hydrator = new Hydrator($this->sourceFs, $this->targetFs, $reader, new Finder($this->sourceFs));

        $this->write('a-dist', '<%var-with-dashes%> = <%dash-dash-dash%>');

        $this->hydrator->hydrate('dev');
        $this->assertTargetContent('poney = licorne', 'a');
    }

    /**
     * @dataProvider providerTestHydrateWithADifferentSystemEnvironment
     */
    public function testHydrateWithADifferentSystemEnvironment(string $env, string $systemEnv, string $expectedA, string $expectedList, string $expectedDirective): void
    {
        $reader = new InMemoryReader([
            'poney:dev' => 'poney_d',
            'poney:staging' => 'poney_s',
            '@licorne:dev' => 'licorne_d',
            '@licorne:staging' => 'licorne_s',
            'env:dev' => ['d', 'e', 'v'],
            'env:staging' => ['s', 't', 'a'],
            '@dsn:dev' => ['d', 'e', 'v'],
            '@dsn:staging' => ['s', 't', 'a'],
        ]);

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

    public function providerTestHydrateWithADifferentSystemEnvironment(): array
    {
        $dev = "d\ne\nv";
        $sta = "s\nt\na";

        return [
            ['dev', 'dev', 'poney_d = licorne_d', "$dev\n$dev", 'dev = dev'],
            ['dev', 'staging', 'poney_d = licorne_s', "$sta\n$dev", 'sta = dev'],
            ['staging', 'dev', 'poney_s = licorne_d', "$dev\n$sta", 'dev = sta'],
            ['staging', 'staging', 'poney_s = licorne_s', "$sta\n$sta", 'sta = sta'],
        ];
    }
    
    /**
     * @group nested
     */
    public function testNestedVariables(): void
    {
        $this->expectException(\RuntimeException::class);

        $reader = new InMemoryReader([
            'meat:dev' => 'pony',
            'burger:dev' => "<%meat%> with sparkles",
            'customer:dev' => 'cloudman',
        ]);

        $hydrator = new Hydrator($this->sourceFs, $this->targetFs, $reader, new Finder($this->sourceFs));
        
        $this->write('a-dist', "<%burger%>");
        $hydrator->hydrate('dev');
    }
}
