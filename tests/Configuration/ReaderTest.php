<?php

declare(strict_types = 1);

namespace Karma\Configuration;

use Karma\Configuration;
use Gaufrette\Filesystem;
use Karma\Filesystem\Adapters\Memory;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__ . '/ParserTestCase.php';

class ReaderTest extends ParserTestCase
{
    private Reader
        $reader;

    protected function setUp(): void
    {
        parent::setUp();

        $variables = $this->parser->parse(self::MASTERFILE_PATH);
        $this->reader = new Reader($variables, $this->parser->externalVariables());
    }

    public static function providerTestRead(): array
    {
        return [
            // master.conf
            ['print_errors', 'prod', false],
            ['print_errors', 'preprod', false],
            ['print_errors', 'recette', true],
            ['print_errors', 'qualif', true],
            ['print_errors', 'integration', true],
            ['print_errors', 'dev', true],

            ['debug', 'prod', false],
            ['debug', 'preprod', false],
            ['debug', 'recette', false],
            ['debug', 'qualif', false],
            ['debug', 'integration', false],
            ['debug', 'dev', true],

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
            ['apiKey', 'prod', 'qd4#qs64d6q6=fgh4f6ùftgg==sdr'],
            ['apiKey', 'default', 'qd4#qs64d6q6=fgh4f6ùftgg==sdr'],

            ['my.var.with.subnames', 'dev', 21],
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
            ['list.ok', 'other', [2, 0, 'third', false, null]],
            ['list.ok', 'staging2', []],
            ['list.ok', 'staging3', []],
            ['list.ok', 'staging_default', ['single value with blanks']],
            ['list.ok', 'prod_default', ['single value with blanks']],

            ['list.notlist', 'dev', 'string[weird'],
            ['list.notlist', 'staging', 'string]weird'],
            ['list.notlist', 'prod', '[string[weird'],
            ['list.notlist', 'preprod', 'string]'],
            ['list.notlist', 'other', 'arr[]'],
            ['list.notlist', 'staging2', 'arr[tung]'],
            ['list.notlist', 'staging3', '[1,2,3]4'],
            ['list.notlist', 'staging_default', '[string'],
            ['list.notlist', 'prod_default', '[string'],

            ['list.notlist', 'string1', '[]]'],
            ['list.notlist', 'string2', '[[]'],
            ['list.notlist', 'string3', '[[]]'],
            ['list.notlist', 'string4', '[][]'],

            ['variable-name-with-dashes', 'default', 'poney'],

            ['redis_prefix', 'default', 'prefix:ending:with:semi:colon:'],
        ];
    }

    #[DataProvider('providerTestRead')]
    public function testRead(string $variable, string $environment, $expectedValue): void
    {
        self::assertSame($expectedValue, $this->reader->read($variable, $environment));
    }

    #[DataProvider('providerTestRead')]
    public function testReadWithDefaultEnvironment(string $variable, string $environment, $expectedValue): void
    {
        $this->reader->setDefaultEnvironment($environment);

        self::assertSame($expectedValue, $this->reader->read($variable));
    }

    #[DataProvider('providerTestReadNotFoundValue')]
    public function testReadNotFoundValue(string $variable, string $environment): void
    {
        $this->expectException(\RuntimeException::class);

        $this->reader->read($variable, $environment);
    }

    public static function providerTestReadNotFoundValue(): array
    {
        return [
            ['thisvariabledoesnotexist', 'dev'],
            ['server', 'dev'],
        ];
    }

    public function testGetAllVariables(): void
    {
        $variables = $this->reader->allVariables();
        sort($variables);

        $expected = ['print_errors', 'debug', 'gourdin', 'server', 'tva', 'apiKey', 'my.var.with.subnames', 'param', 'user', 'list.ok', 'list.notlist', 'variable-name-with-dashes', 'redis_prefix'];
        sort($expected);

        self::assertSame($expected, $variables);
    }

    #[DataProvider('providerTestGetAllValuesForEnvironment')]
    public function testGetAllValuesForEnvironment(string $environment, array $expectedValues): void
    {
        $variables = $this->reader->allValuesForEnvironment($environment);
        self::assertIsArray($variables);

        $keys = array_keys($variables);
        $expectedKeys = array_keys($expectedValues);
        sort($keys);
        sort($expectedKeys);
        self::assertSame($expectedKeys, $keys);

        foreach($keys as $variable)
        {
            self::assertSame($expectedValues[$variable], $variables[$variable], "Value for $variable");
        }
    }

    public static function providerTestGetAllValuesForEnvironment(): array
    {
        return [
            ['dev', [
                'print_errors' => true,
                'debug' => true,
                'gourdin' => 2,
                'server' => Configuration::NOT_FOUND,
                'tva' => 19.0,
                'apiKey' => '=2',
                'my.var.with.subnames' => 21,
                'param' => '${param}',
                'user' => 'root',
                'list.ok' => ['one', 'two', 'three'],
                'list.notlist' => 'string[weird',
                'variable-name-with-dashes' => 'poney',
                'redis_prefix' => 'prefix:ending:with:semi:colon:',
            ]],
            ['prod', [
                'print_errors' => false,
                'debug' => false,
                'gourdin' => 0,
                'server' => 'sql21',
                'tva' => 19.6,
                'apiKey' => 'qd4#qs64d6q6=fgh4f6ùftgg==sdr',
                'my.var.with.subnames' => 21,
                'param' => Configuration::NOT_FOUND,
                'user' => 'root',
                'list.ok' => ['alone'],
                'list.notlist' => '[string[weird',
                'variable-name-with-dashes' => 'poney',
                'redis_prefix' => 'prefix:ending:with:semi:colon:',
            ]],
        ];
    }

    #[DataProvider('providerTestDiff')]
    public function testDiff(string $environment1, string $environment2, array $expectedDiff): void
    {
        $diff = $this->reader->compareEnvironments($environment1, $environment2);

        self::assertSame($expectedDiff, $diff);
    }

    public static function providerTestDiff(): array
    {
        return [
            ['dev', 'prod', [
                'print_errors' => [true, false],
                'debug' => [true, false],
                'gourdin' => [2, 0],
                'tva' => [19.0, 19.6],
                'server' => [Configuration::NOT_FOUND, 'sql21'],
                'apiKey' => ['=2', 'qd4#qs64d6q6=fgh4f6ùftgg==sdr'],
                'param' => ['${param}', Configuration::NOT_FOUND],
                'list.ok' => [['one', 'two', 'three'], ['alone']],
                'list.notlist' => ['string[weird', '[string[weird'],
            ]],
            ['preprod', 'prod', [
                'gourdin' => [1, 0],
                'tva' => [20.5, 19.6],
                'server' => ['prod21', 'sql21'],
                'list.ok' => ['not_a_list', ['alone']],
                'list.notlist' => ['string]', '[string[weird'],
            ]],
        ];
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
    preprod = <external>
    default = userdb
CONFFILE;

        $externalContent1 = <<<CONFFILE
[variables]
db.pass:
    prod = veryComplexPass
CONFFILE;

        $externalContent2 = <<<CONFFILE
[variables]
db.user:
    preprod = foobar
CONFFILE;

        $files = [
            'master.conf' => $masterContent,
            'external1.conf' => $externalContent1,
            'external2.conf' => $externalContent2,
        ];

        $parser = new Parser(new Filesystem(new Memory($files)));

        $variables = $parser->enableIncludeSupport()
            ->enableExternalSupport()
            ->parse(self::MASTERFILE_PATH);

        $reader = new Reader($variables, $parser->externalVariables());

        $expected = [
            'db.pass' => [
                'dev' => 1234,
                'prod' => 'veryComplexPass',
                'preprod' => 'root',
            ],
            'db.user' => [
                'dev' => 'userdb',
                'prod' => 'userdb',
                'preprod' => 'foobar',
            ],
        ];

        foreach($expected as $variable => $info)
        {
            foreach($info as $environment => $expectedValue)
            {
                self::assertSame($expectedValue, $reader->read($variable, $environment));
            }
        }
    }

    #[DataProvider('providerTestExternalError')]
    public function testExternalError(string $contentMaster): void
    {
        $this->expectException(\RuntimeException::class);

        $parser = new Parser(new Filesystem(new Memory([
            self::MASTERFILE_PATH => $contentMaster,
            'empty.conf' => '',
            'totoDev.conf' => <<<CONFFILE
[Variables]
toto:
    dev = someDevValue
CONFFILE
        ])));

        $parser
            ->enableIncludeSupport()
            ->enableExternalSupport();

        $variables = $parser->parse(self::MASTERFILE_PATH);
        $reader = new Reader($variables, $parser->externalVariables());
        $reader->read('toto', 'prod');
    }

    public static function providerTestExternalError(): array
    {
        return [
            'external variable without any external file' => [<<<CONFFILE
[variables]
toto :
    prod = <external>
CONFFILE
            ],
            'external variable not found in external file' => [<<<CONFFILE
[externals]
empty.conf

[variables]
toto :
    prod = <external>
CONFFILE
            ],
            'external variable found in external file but not for the correct environment' => [<<<CONFFILE
[externals]
totoDev.conf

[variables]
toto :
    prod = <external>
CONFFILE
            ],
        ];
    }

    public function testExternalConflict(): void
    {
        $this->expectException(\RuntimeException::class);

        $contentMaster = <<<CONFFILE
[externals]
ext1.conf
ext2.conf

[variables]
v1:
    prod = <external>
CONFFILE;

    $contentExt1 = <<< CONFFILE
[variables]
v1:
    prod = foo
CONFFILE;

    $contentExt2 = <<< CONFFILE
[variables]
v1:
    prod = bar
CONFFILE;

        $parser = new Parser(new Filesystem(new Memory([
            self::MASTERFILE_PATH => $contentMaster,
            'ext1.conf' => $contentExt1,
            'ext2.conf' => $contentExt2
        ])));

        $parser
            ->enableIncludeSupport()
            ->enableExternalSupport();

        $variables = $parser->parse(self::MASTERFILE_PATH);
        $reader = new Reader($variables, $parser->externalVariables());
        $reader->read('v1', 'prod');
    }

    public function testOverrideVariable(): void
    {
        $environment = 'prod';

        self::assertFalse($this->reader->read('print_errors', $environment));
        self::assertFalse($this->reader->read('debug', $environment));
        self::assertSame(0, $this->reader->read('gourdin', $environment));

        $this->reader->overrideVariable('debug', true);

        self::assertFalse($this->reader->read('print_errors', $environment));
        self::assertTrue($this->reader->read('debug', $environment), 'read() must return the overriden value');
        self::assertSame(0, $this->reader->read('gourdin', $environment));

        $this->reader->overrideVariable('print_errors', true);
        $this->reader->overrideVariable('debug', null);

        self::assertTrue($this->reader->read('print_errors', $environment), 'read() must return the overriden value');
        self::assertNull($this->reader->read('debug', $environment), 'read() must return the overriden value');
        self::assertSame(0, $this->reader->read('gourdin', $environment));
    }

    public function testOverrideUnknownVariable(): void
    {
        $environment = 'prod';
        $variable = 'UNKNOWN';
        $value = 'some Value';

        $exceptionRaised = false;

        try
        {
            $this->reader->read($variable, $environment);
        }
        catch(\RuntimeException $e)
        {
            $exceptionRaised = true;
        }

        self::assertTrue($exceptionRaised, 'An exception must be raised when reading unknown variable');

        $this->reader->overrideVariable($variable, $value);
        self::assertSame($value, $this->reader->read($variable, $environment), 'Read an overriden unknown variable must not raise an exception');
    }

    public function testCustomData(): void
    {
        $var = 'param';

        // No error while setting unused custom data
        $this->reader->setCustomData('NotExist', 'NoError');

        self::assertSame('${param}', $this->reader->read($var, 'dev'));
        self::assertSame('Some${nested}param', $this->reader->read($var, 'staging'));

        $this->reader->setCustomData('PARAM', 'caseSensitive');

        self::assertSame('${param}', $this->reader->read($var, 'dev'));
        self::assertSame(
            ['none', 'nest${param}ed', '${nested}', 'double_${param}_${param}', '${nested}${param}'],
            $this->reader->read($var, 'demo')
        );
        self::assertSame('Some${nested}param', $this->reader->read($var, 'staging'));

        $this->reader->setCustomData('param', 'foobar');

        self::assertSame('foobar', $this->reader->read($var, 'dev'));
        self::assertSame(
            ['none', 'nestfoobared', '${nested}', 'double_foobar_foobar', '${nested}foobar'],
            $this->reader->read($var, 'demo')
        );
        self::assertSame('Some${nested}param', $this->reader->read($var, 'staging'));

        $this->reader->setCustomData('nested', 'Base');

        self::assertSame('foobar', $this->reader->read($var, 'dev'));
        self::assertSame(
            ['none', 'nestfoobared', 'Base', 'double_foobar_foobar', 'Basefoobar'],
            $this->reader->read($var, 'demo')
        );
        self::assertSame('SomeBaseparam', $this->reader->read($var, 'staging'));
    }

    public function testCustomDataEdgeCases(): void
    {
        $contentMaster = <<<CONFFILE
[variables]
v1:
    dev = foo
    staging = foo\${fo}\$foo
    integration = \${fooz}\${foo}foo
    prod = \${foo
CONFFILE;

        $parser = new Parser(new Filesystem(new Memory([
            self::MASTERFILE_PATH => $contentMaster,
        ])));

        $reader = new Reader($parser->parse(self::MASTERFILE_PATH), []);

        $reader->setCustomData('foo', 'bar');

        self::assertSame('foo', $reader->read('v1', 'dev'));
        self::assertSame('foo${fo}$foo', $reader->read('v1', 'staging'));
        self::assertSame('${fooz}barfoo', $reader->read('v1', 'integration'));
        self::assertSame('${foo', $reader->read('v1', 'prod'));
    }

    public function testGroups(): void
    {
        $masterContent = <<<CONFFILE
[groups]
qa = [ staging, preprod ]
dev = [ dev1, dev2,dev3]
production=[prod]

[variables]
db.pass:
    dev = 1234
    qa = password
    prod = root
db.user:
    dev1 = devuser1
    dev2 = devuser2
    dev3 = devuser3
    qa = qauser
    production = root
    default = nobody
db.cache:
    dev2 = maybe
    qa = sometimes
    preprod = true
    default = false
CONFFILE;

        $parser = new Parser(new Filesystem(new Memory([self::MASTERFILE_PATH => $masterContent])));

        $parser->enableIncludeSupport()
            ->enableExternalSupport()
            ->enableGroupSupport();

        $variables = $parser->parse(self::MASTERFILE_PATH);
        $reader = new Reader($variables, $parser->externalVariables(), $parser->groups());

        $expected = [
            'db.pass' => [
                'staging' => 'password',
                'preprod' => 'password',
                'dev1' => 1234,
                'dev2' => 1234,
                'dev3' => 1234,
                'prod' => 'root',
            ],
            'db.user' => [
                'staging' => 'qauser',
                'preprod' => 'qauser',
                'dev1' => 'devuser1',
                'dev2' => 'devuser2',
                'dev3' => 'devuser3',
                'prod' => 'root',
            ],
            'db.cache' => [
                'staging' => 'sometimes',
                'preprod' => true,
                'dev1' => false,
                'dev2' => 'maybe',
                'dev3' => false,
                'prod' => false,
            ],
        ];

        foreach($expected as $variable => $info)
        {
            foreach($info as $environment => $expectedValue)
            {
                self::assertSame($expectedValue, $reader->read($variable, $environment));
            }
        }
    }

    public function testGroupsAreNotReadable(): void
    {
        $this->expectException(\RuntimeException::class);

        $masterContent = <<<CONFFILE
[groups]
qa = [ staging, preprod ]

[variables]
db.pass:
    dev = 1234
    qa = password
    prod = root
CONFFILE;

        $parser = new Parser(new Filesystem(new Memory([self::MASTERFILE_PATH => $masterContent])));

        $parser->enableIncludeSupport()
            ->enableExternalSupport()
            ->enableGroupSupport();

        $variables = $parser->parse(self::MASTERFILE_PATH);
        $reader = new Reader($variables, $parser->externalVariables(), $parser->groups());
        $reader->read('db.pass', 'qa');
    }

    public function testGroupsAreReadableIfADefaultEnvIsDefined(): void
    {
        $masterContent = <<<CONFFILE
[groups]
qa = [ *staging, preprod ]

[variables]
db.pass:
    dev = 1234
    qa = password
    staging = password_staging
    prod = root
CONFFILE;

        $parser = new Parser(new Filesystem(new Memory([self::MASTERFILE_PATH => $masterContent])));

        $parser->enableIncludeSupport()
            ->enableExternalSupport()
            ->enableGroupSupport();

        $variables = $parser->parse(self::MASTERFILE_PATH);
        $reader = new Reader($variables, $parser->externalVariables(), $parser->groups(), $parser->defaultEnvironmentsForGroups());
        self::assertSame($reader->read('db.pass', 'qa'), $reader->read('db.pass', 'staging'));
    }

    public function testGroupsInDifferentFiles(): void
    {
        $masterContent = <<<CONFFILE
[includes]
group.conf

[variables]
db.pass:
    qa = password
    default = fail
CONFFILE;

        $groupContent = <<<CONFFILE
[groups]
qa = [staging]
CONFFILE;

        $parser = new Parser(new Filesystem(new Memory(array(
            self::MASTERFILE_PATH => $masterContent,
            'group.conf' => $groupContent,
        ))));

        $parser->enableIncludeSupport()
            ->enableExternalSupport()
            ->enableGroupSupport();

        $variables = $parser->parse(self::MASTERFILE_PATH);
        $reader = new Reader($variables, $parser->externalVariables(), $parser->groups());

        self::assertSame('password', $reader->read('db.pass', 'staging'));
    }

    public function testGroupsUsageInExternalFile(): void
    {
        $masterContent = <<<CONFFILE
[externals]
secured.conf

[groups]
qa = [staging]

[variables]
db.pass:
    staging = <external>
    default = fail
CONFFILE;

        $securedContent = <<<CONFFILE
[variables]
db.pass:
    qa = success
CONFFILE;

        $parser = new Parser(new Filesystem(new Memory(array(
            self::MASTERFILE_PATH => $masterContent,
            'secured.conf' => $securedContent,
        ))));

        $parser->enableIncludeSupport()
            ->enableExternalSupport()
            ->enableGroupSupport();

        $variables = $parser->parse(self::MASTERFILE_PATH);
        $reader = new Reader($variables, $parser->externalVariables(), $parser->groups());

        self::assertSame('success', $reader->read('db.pass', 'staging'));
    }

    #[DataProvider('providerTestIsSystem')]
    public function testIsSystem(string $variable, bool $expected): void
    {
        self::assertSame($expected, $this->reader->isSystem($variable));
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
}
