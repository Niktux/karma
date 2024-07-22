<?php

declare(strict_types = 1);

namespace Karma\Configuration;

use Gaufrette\Filesystem;
use Gaufrette\Adapter\InMemory;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__ . '/ParserTestCase.php';

class ParserTest extends ParserTestCase
{
    #[DataProvider('providerTestRead')]
    public function testRead(string $variable, string $environment, $expectedValue): void
    {
        $variables = $this->parser->setEOL("\n")->parse(self::MASTERFILE_PATH);

        self::assertArrayHasKey($variable, $variables);
        self::assertArrayHasKey('env', $variables[$variable]);
        self::assertArrayHasKey($environment, $variables[$variable]['env']);
        self::assertSame($expectedValue, $variables[$variable]['env'][$environment]);
    }

    public static function providerTestRead(): array
    {
        return [
            // master.conf
            ['print_errors', 'prod', false],
            ['print_errors', 'preprod', false],
            ['print_errors', 'default', true],

            ['debug', 'dev', true],
            ['debug', 'default', false],

            ['gourdin', 'prod', 0],
            ['gourdin', 'preprod', 1],
            ['gourdin', 'recette', 1],
            ['gourdin', 'qualif', 1],
            ['gourdin', 'integration', null],
            ['gourdin', 'dev', 2],
            ['gourdin', 'staging', 'string with blanks'],

            ['server', 'prod', 'sql21'],
            ['server', 'preprod', 'prod21'],
            ['server', 'recette', 'rec21'],
            ['server', 'qualif', 'rec21'],

            ['tva', 'dev', 19.0],
            ['tva', 'preprod', 20.5],
            ['tva', 'default', 19.6],

            ['apiKey', 'dev', '=2'],
            ['apiKey', 'recette', ''],
            ['apiKey', 'default', 'qd4#qs64d6q6=fgh4f6ùftgg==sdr'],

            ['my.var.with.subnames', 'default', 21],

            ['param', 'dev', '${param}'],
            ['param', 'staging', 'Some${nested}param'],
            ['param', 'demo', ['none', 'nest${param}ed', '${nested}', 'double_${param}_${param}', '${nested}${param}']],

            // db.conf
            ['user', 'default', 'root'],

            // lists
            ['list.ok', 'dev', ['one', 'two', 'three']],
            ['list.ok', 'staging', ['one', 'two']],
            ['list.ok', 'prod', ['alone']],
            ['list.ok', 'preprod', 'not_a_list'],
            ['list.ok', 'default', ['single value with blanks']],
            ['list.ok', 'other', [2, 0, 'third', false, null]],
            ['list.ok', 'staging2', []],
            ['list.ok', 'staging3', []],

            ['list.notlist', 'dev', 'string[weird'],
            ['list.notlist', 'staging', 'string]weird'],
            ['list.notlist', 'prod', '[string[weird'],
            ['list.notlist', 'default', '[string'],
            ['list.notlist', 'preprod', 'string]'],
            ['list.notlist', 'other', 'arr[]'],
            ['list.notlist', 'staging2', 'arr[tung]'],
            ['list.notlist', 'staging3', '[1,2,3]4'],

            ['list.notlist', 'string1', '[]]'],
            ['list.notlist', 'string2', '[[]'],
            ['list.notlist', 'string3', '[[]]'],
            ['list.notlist', 'string4', '[][]'],

            ['variable-name-with-dashes', 'default', 'poney'],
            ['redis_prefix', 'default', 'prefix:ending:with:semi:colon:'],
        ];
    }

    #[DataProvider('providerTestSyntaxError')]
    public function testSyntaxError(string $contentMaster): void
    {
        $this->expectException(\RuntimeException::class);

        $this->parser = new Parser(new Filesystem(new InMemory([
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
        ])));

        $this->parser
            ->enableIncludeSupport()
            ->enableExternalSupport()
            ->enableGroupSupport()
            ->parse(self::MASTERFILE_PATH);
    }

    public static function providerTestSyntaxError(): array
    {
        return [
            'missing =' => [<<<CONFFILE
[variables]
print_errors:
    default:true
CONFFILE
            ],
            'missing variables' => [<<<CONFFILE
print_errors:
    default:true
CONFFILE
            ],
            'include not found' => [<<<CONFFILE
[includes]
empty.conf
notfound.conf
CONFFILE
            ],
            'variables mispelled' => [<<<CONFFILE
[variable]
toto:
    tata = titi
CONFFILE
            ],
            'missing variable name' => [<<<CONFFILE
[variables]
prod = value
CONFFILE
            ],
            'duplicated variables' => [<<<CONFFILE
[variables]
toto:
    prod = tata
toto:
    dev = titi
CONFFILE
            ],
            'duplicated variables with some spaces before' => [<<<CONFFILE
[variables]
 toto:
    prod = tata
toto:
    dev = titi
CONFFILE
            ],
            'duplicated variables with some spaces after' => [<<<CONFFILE
[variables]
toto :
    prod = tata
toto:
    dev = titi
CONFFILE
            ],
            'duplicated variables with some spaces both' => [<<<CONFFILE
[variables]
toto :
    prod = tata
 toto:
    dev = titi
CONFFILE
            ],
            'duplicated variables in different files' => [<<<CONFFILE
[includes]
vicious.conf
[variables]
viciousDuplicatedVariable:
    prod = tata
CONFFILE
            ],
            'duplicated environment' => [<<<CONFFILE
[variables]
toto:
    prod = tata
    preprod, recette = titi
    dev, prod, qualif = tutu
CONFFILE
            ],
            'missing : after variable name' => [<<<CONFFILE
[variables]
toto
    prod = 2
CONFFILE
            ],
            'variable name syntax error' => [<<<CONFFILE
[variables]
toto =
    prod = 2
CONFFILE
            ],
            'variable without value' => [<<<CONFFILE
[variables]
toto :
    prod = 2
tata :
titi :
    dev = 3
CONFFILE
            ],
            'last variable without value' => [<<<CONFFILE
[variables]
toto :
    prod = 2
tata :
    dev = 3
titi :
CONFFILE
            ],
            'invalid name format for include file' => [<<<CONFFILE
[includes]
notADotConfFile
CONFFILE
            ],
            'comments not on its own line' => [<<<CONFFILE
[variables]  # illegal comment
toto:
    foo = bar
CONFFILE
            ],
            'groups syntax error : missing [] #1' => [<<<CONFFILE
[groups]
name = foobar
CONFFILE
            ],
            'groups syntax error : missing [] #2' => [<<<CONFFILE
[groups]
name = [foobar
CONFFILE
            ],
            'groups syntax error : missing [] #3' => [<<<CONFFILE
[groups]
name = foobar]
CONFFILE
            ],
            'groups syntax error : missing [] #4' => [<<<CONFFILE
[groups]
name = [foob]ar
CONFFILE
            ],
            'groups syntax error : missing [] #5' => [<<<CONFFILE
[groups]
name = fo[obar]
CONFFILE
            ],
            'groups syntax error : missing [] #6' => [<<<CONFFILE
[groups]
name = fo[ob]ar
CONFFILE
            ],
            'groups syntax error : not a single list' => [<<<CONFFILE
[groups]
name = [a,b,c][d,e,f]
CONFFILE
            ],
            'groups syntax error : empty env #1' => [<<<CONFFILE
[groups]
name = []
CONFFILE
            ],
            'groups syntax error : empty env #2' => [<<<CONFFILE
[groups]
name = [dev,staging,]
CONFFILE
            ],
            'groups syntax error : empty env #3' => [<<<CONFFILE
[groups]
name = [,dev,staging]
CONFFILE
            ],
            'groups syntax error : empty env #4' => [<<<CONFFILE
[groups]
name = [dev,,staging]
CONFFILE
            ],
            'groups syntax error : duplicated group name' => [<<<CONFFILE
[groups]
prod = [dev,staging]
prod = [preprod]
CONFFILE
            ],
            'groups syntax error : duplicated environment in same group' => [<<<CONFFILE
[groups]
prod = [dev,staging, dev]
CONFFILE
            ],
            'groups syntax error : circular reference' => [<<<CONFFILE
[groups]
foo = [bar]
bar = [baz]
CONFFILE
            ],
            'groups syntax error : env in many groups' => [<<<CONFFILE
[groups]
foo = [baz]
bar = [baz]
CONFFILE
            ],
            'spaces in variable name' => [<<<CONFFILE
[variables]
var with spaces:
    default = false
CONFFILE
            ],
            '= in variable name' => [<<<CONFFILE
[variables]
invalid=varname:
    default = false
CONFFILE
            ],
        ];
    }

    public function testEmptyFile(): void
    {
        $masterContent = '';

        $parser = new Parser(new Filesystem(new InMemory([self::MASTERFILE_PATH => $masterContent])));

        $variables = $parser->enableIncludeSupport()
            ->enableExternalSupport()
            ->enableGroupSupport()
            ->parse(self::MASTERFILE_PATH);

        self::assertEmpty($variables);
    }

    public function testExternal(): void
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

        $files = [
            self::MASTERFILE_PATH => $masterContent,
            'external1.conf' => $externalContent1,
            'external2.conf' => $externalContent2,
        ];

        $parser = new Parser(new Filesystem(new InMemory($files)));

        $parser->enableIncludeSupport()
            ->enableExternalSupport();

        $variables = $parser->parse(self::MASTERFILE_PATH);

        $expected = [
            'db.pass' => [
                'dev' => 1234,
                'prod' => '<external>',
                'default' => 'root',
            ],
            'db.user' => [
                'staging' => '<external>',
                'default' => 'root',
            ],
        ];

        foreach($expected as $variable => $info)
        {
            foreach($info as $environment => $expectedValue)
            {
                self::assertArrayHasKey($variable, $variables);
                self::assertArrayHasKey('env', $variables[$variable]);
                self::assertArrayHasKey($environment, $variables[$variable]['env']);
                self::assertSame($expectedValue, $variables[$variable]['env'][$environment]);
            }
        }
    }

    public function testGroups(): void
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

        $parser = new Parser(new Filesystem(new InMemory([self::MASTERFILE_PATH => $masterContent])));

        $parser->enableIncludeSupport()
            ->enableExternalSupport()
            ->enableGroupSupport();

        $variables = $parser->parse(self::MASTERFILE_PATH);

        $expected = [
            'db.pass' => [
                'dev' => 1234,
                'qa' => 'password',
                'prod' => '<external>',
            ],
            'db.user' => [
                'dev1' => 'devuser1',
                'dev2' => 'devuser2',
                'dev3' => 'devuser3',
                'qa' => 'qauser',
            ],
            'db.cache' => [
                'preprod' => true,
                'default' => false,
            ],
        ];

        foreach($expected as $variable => $info)
        {
            foreach($info as $environment => $expectedValue)
            {
                self::assertArrayHasKey($variable, $variables);
                self::assertArrayHasKey('env', $variables[$variable]);
                self::assertArrayHasKey($environment, $variables[$variable]['env']);
                self::assertSame($expectedValue, $variables[$variable]['env'][$environment]);
            }
        }

        $groups = $parser->getGroups();

        $expected = [
            'dev' => ['dev1', 'dev2', 'dev3'],
            'qa' => ['staging', 'preprod'],
            'production' => ['prod'],
        ];

        $this->assertSameArraysExceptOrder($expected, $groups);
    }

    #[DataProvider('providerTestIsSystem')]
    public function testIsSystem(string $variable, bool $expected): void
    {
        $this->parser->parse(self::MASTERFILE_PATH);
        self::assertSame($expected, $this->parser->isSystem($variable));
    }

    public static function providerTestIsSystem(): array
    {
        return [
            ['gourdin', true],
            ['tva', true],
            ['debug', false],
            ['list.ok', false],
        ];
    }

    public function testDefaultEnvironmentForGroups(): void
    {
        $masterContent = <<<CONFFILE
[groups]
# comment
qa = [ *staging, preprod ]
dev = [ dev1,  *  dev2,dev3]
 # comment
production=[prod]
CONFFILE;

        $parser = new Parser(new Filesystem(new InMemory([self::MASTERFILE_PATH => $masterContent])));

        $parser->enableIncludeSupport()
            ->enableExternalSupport()
            ->enableGroupSupport()
            ->parse(self::MASTERFILE_PATH);

        $groups = $parser->getGroups();
        $expected = [
            'dev' => ['dev1', 'dev2', 'dev3'],
            'qa' => ['staging', 'preprod'],
            'production' => ['prod'],
        ];

        $this->assertSameArraysExceptOrder($expected, $groups);

        $envs = $parser->getDefaultEnvironmentsForGroups();
        $expected = [
            'dev' => 'dev2',
            'qa' => 'staging',
            'production' => null,
        ];

        $this->assertSameArraysExceptOrder($expected, $envs);
    }

    private function assertSameArraysExceptOrder(array $expected, array $result): void
    {
        ksort($result);
        ksort($expected);

        self::assertSame($expected, $result);
    }

    public function testMultipleDefaultEnvironmentForASameGroup(): void
    {
        $this->expectException(\RuntimeException::class);

        $masterContent = <<<CONFFILE
[groups]
dev = [ dev1, *dev2,*dev3]
CONFFILE;

        $parser = new Parser(new Filesystem(new InMemory([self::MASTERFILE_PATH => $masterContent])));

        $parser->enableIncludeSupport()
            ->enableExternalSupport()
            ->enableGroupSupport()
            ->parse(self::MASTERFILE_PATH);
    }

    public function testFileStartsWithComment(): void
    {
        $masterContent = <<<CONFFILE

# This is a comment
# like for those whose put copyright headers
# or love to tell about their lifes

[vArIaBlEs]
toto:
    default = tata

CONFFILE;

        $parser = new Parser(new Filesystem(new InMemory([self::MASTERFILE_PATH => $masterContent])));

        $variables = $parser->parse(self::MASTERFILE_PATH);

        self::assertArrayHasKey('toto', $variables);
    }
}
