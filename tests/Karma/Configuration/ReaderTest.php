<?php

namespace Karma\Configuration;

use Karma\Configuration;
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
            array('apiKey', 'prod', 'qd4#qs64d6q6=fgh4f6ùftgg==sdr'),
            array('apiKey', 'default', 'qd4#qs64d6q6=fgh4f6ùftgg==sdr'),

            array('my.var.with.subnames', 'dev', 21),
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
            array('list.ok', 'other', array(2, 0, 'third', false, null)),
            array('list.ok', 'staging2', array()),
            array('list.ok', 'staging3', array()),
            array('list.ok', 'staging_default', array('single value with blanks')),
            array('list.ok', 'prod_default', array('single value with blanks')),

            array('list.notlist', 'dev', 'string[weird'),
            array('list.notlist', 'staging', 'string]weird'),
            array('list.notlist', 'prod', '[string[weird'),
            array('list.notlist', 'preprod', 'string]'),
            array('list.notlist', 'other', 'arr[]'),
            array('list.notlist', 'staging2', 'arr[tung]'),
            array('list.notlist', 'staging3', '[1,2,3]4'),
            array('list.notlist', 'staging_default', '[string'),
            array('list.notlist', 'prod_default', '[string'),

            array('list.notlist', 'string1', '[]]'),
            array('list.notlist', 'string2', '[[]'),
            array('list.notlist', 'string3', '[[]]'),
            array('list.notlist', 'string4', '[][]'),

            array('variable-name-with-dashes', 'default', 'poney'),

            array('redis_prefix', 'default', 'prefix:ending:with:semi:colon:'),
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

        $expected = array('print_errors', 'debug', 'gourdin', 'server', 'tva', 'apiKey', 'my.var.with.subnames', 'param', 'user', 'list.ok', 'list.notlist', 'variable-name-with-dashes', 'redis_prefix');
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
                'param' => '${param}',
                'user' => 'root',
                'list.ok' => array('one', 'two', 'three'),
                'list.notlist' => 'string[weird',
                'variable-name-with-dashes' => 'poney',
                'redis_prefix' => 'prefix:ending:with:semi:colon:',
            )),
            array('prod', array(
                'print_errors' => false,
                'debug' => false,
                'gourdin' => 0,
                'server' => 'sql21',
                'tva' => 19.6,
                'apiKey' => 'qd4#qs64d6q6=fgh4f6ùftgg==sdr',
                'my.var.with.subnames' => 21,
                'param' => Configuration::NOT_FOUND,
                'user' => 'root',
                'list.ok' => array('alone'),
                'list.notlist' => '[string[weird',
                'variable-name-with-dashes' => 'poney',
                'redis_prefix' => 'prefix:ending:with:semi:colon:',
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
                'apiKey' => array('=2', 'qd4#qs64d6q6=fgh4f6ùftgg==sdr'),
                'param' => array('${param}', Configuration::NOT_FOUND),
                'list.ok' => array(array('one', 'two', 'three'), array('alone')),
                'list.notlist' => array('string[weird', '[string[weird'),
            )),
            array('preprod', 'prod', array(
                'gourdin' => array(1, 0),
                'tva' => array(20.5, 19.6),
                'server' => array('prod21', 'sql21'),
                'list.ok' => array('not_a_list', array('alone')),
                'list.notlist' => array('string]', '[string[weird'),
            )),
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

        $files = array(
            'master.conf' => $masterContent,
            'external1.conf' => $externalContent1,
            'external2.conf' => $externalContent2,
        );

        $parser = new Parser(new Filesystem(new InMemory($files)));

        $variables = $parser->enableIncludeSupport()
            ->enableExternalSupport()
            ->parse(self::MASTERFILE_PATH);

        $reader = new Reader($variables, $parser->getExternalVariables());

        $expected = array(
            'db.pass' => array(
                'dev' => 1234,
                'prod' => 'veryComplexPass',
                'preprod' => 'root',
            ),
            'db.user' => array(
                'dev' => 'userdb',
                'prod' => 'userdb',
                'preprod' => 'foobar',
            ),
        );

        foreach($expected as $variable => $info)
        {
            foreach($info as $environment => $expectedValue)
            {
                $this->assertSame($expectedValue, $reader->read($variable, $environment));
            }
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
            'empty.conf' => '',
            'totoDev.conf' => <<<CONFFILE
[Variables]
toto:
    dev = someDevValue
CONFFILE
        ))));

        $parser
            ->enableIncludeSupport()
            ->enableExternalSupport();

        $variables = $parser->parse(self::MASTERFILE_PATH);
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
            'external variable found in external file but not for the correct environment' => array(<<<CONFFILE
[externals]
totoDev.conf

[variables]
toto :
    prod = <external>
CONFFILE
            ),
        );
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testExternalConflict()
    {
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

        $parser = new Parser(new Filesystem(new InMemory(array(
            self::MASTERFILE_PATH => $contentMaster,
            'ext1.conf' => $contentExt1,
            'ext2.conf' => $contentExt2
        ))));

        $parser
            ->enableIncludeSupport()
            ->enableExternalSupport();

        $variables = $parser->parse(self::MASTERFILE_PATH);
        $reader = new Reader($variables, $parser->getExternalVariables());
        $reader->read('v1', 'prod');
    }

    public function testOverrideVariable()
    {
        $environment = 'prod';

        $this->assertSame(false, $this->reader->read('print_errors', $environment));
        $this->assertSame(false, $this->reader->read('debug', $environment));
        $this->assertSame(0, $this->reader->read('gourdin', $environment));

        $this->reader->overrideVariable('debug', true);

        $this->assertSame(false, $this->reader->read('print_errors', $environment));
        $this->assertSame(true, $this->reader->read('debug', $environment), 'read() must return the overriden value');
        $this->assertSame(0, $this->reader->read('gourdin', $environment));

        $this->reader->overrideVariable('print_errors', true);
        $this->reader->overrideVariable('debug', null);

        $this->assertSame(true, $this->reader->read('print_errors', $environment), 'read() must return the overriden value');
        $this->assertSame(null, $this->reader->read('debug', $environment), 'read() must return the overriden value');
        $this->assertSame(0, $this->reader->read('gourdin', $environment));
    }

    public function testOverrideUnknownVariable()
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

        $this->assertTrue($exceptionRaised, 'An exception must be raised when reading unknown variable');

        $this->reader->overrideVariable($variable, $value);
        $this->assertSame($value, $this->reader->read($variable, $environment), 'Read an overriden unknown variable must not raise an exception');
    }

    public function testCustomData()
    {
        $var = 'param';

        // No error while setting unused custom data
        $this->reader->setCustomData('NotExist', 'NoError');

        $this->assertSame('${param}', $this->reader->read($var, 'dev'));
        $this->assertSame('Some${nested}param', $this->reader->read($var, 'staging'));

        $this->reader->setCustomData('PARAM', 'caseSensitive');

        $this->assertSame('${param}', $this->reader->read($var, 'dev'));
        $this->assertSame('Some${nested}param', $this->reader->read($var, 'staging'));

        $this->reader->setCustomData('param', 'foobar');

        $this->assertSame('foobar', $this->reader->read($var, 'dev'));
        $this->assertSame('Some${nested}param', $this->reader->read($var, 'staging'));

        $this->reader->setCustomData('nested', 'Base');

        $this->assertSame('foobar', $this->reader->read($var, 'dev'));
        $this->assertSame('SomeBaseparam', $this->reader->read($var, 'staging'));
    }

    public function testCustomDataEdgeCases()
    {
        $contentMaster = <<<CONFFILE
[variables]
v1:
    dev = foo
    staging = foo\${fo}\$foo
    integration = \${fooz}\${foo}foo
    prod = \${foo
CONFFILE;

        $parser = new Parser(new Filesystem(new InMemory(array(
            self::MASTERFILE_PATH => $contentMaster,
        ))));

        $reader = new Reader($parser->parse(self::MASTERFILE_PATH), array());

        $reader->setCustomData('foo', 'bar');

        $this->assertSame('foo', $reader->read('v1', 'dev'));
        $this->assertSame('foo${fo}$foo', $reader->read('v1', 'staging'));
        $this->assertSame('${fooz}barfoo', $reader->read('v1', 'integration'));
        $this->assertSame('${foo', $reader->read('v1', 'prod'));
    }

    public function testGroups()
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

        $parser = new Parser(new Filesystem(new InMemory(array(self::MASTERFILE_PATH => $masterContent))));

        $parser->enableIncludeSupport()
            ->enableExternalSupport()
            ->enableGroupSupport();

        $variables = $parser->parse(self::MASTERFILE_PATH);
        $reader = new Reader($variables, $parser->getExternalVariables(), $parser->getGroups());

        $expected = array(
            'db.pass' => array(
                'staging' => 'password',
                'preprod' => 'password',
                'dev1' => 1234,
                'dev2' => 1234,
                'dev3' => 1234,
                'prod' => 'root',
            ),
            'db.user' => array(
                'staging' => 'qauser',
                'preprod' => 'qauser',
                'dev1' => 'devuser1',
                'dev2' => 'devuser2',
                'dev3' => 'devuser3',
                'prod' => 'root',
            ),
            'db.cache' => array(
                'staging' => 'sometimes',
                'preprod' => true,
                'dev1' => false,
                'dev2' => 'maybe',
                'dev3' => false,
                'prod' => false,
            ),
        );

        foreach($expected as $variable => $info)
        {
            foreach($info as $environment => $expectedValue)
            {
                $this->assertSame($expectedValue, $reader->read($variable, $environment));
            }
        }
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testGroupsAreNotReadable()
    {
        $masterContent = <<<CONFFILE
[groups]
qa = [ staging, preprod ]

[variables]
db.pass:
    dev = 1234
    qa = password
    prod = root
CONFFILE;

        $parser = new Parser(new Filesystem(new InMemory(array(self::MASTERFILE_PATH => $masterContent))));

        $parser->enableIncludeSupport()
            ->enableExternalSupport()
            ->enableGroupSupport();

        $variables = $parser->parse(self::MASTERFILE_PATH);
        $reader = new Reader($variables, $parser->getExternalVariables(), $parser->getGroups());
        $reader->read('db.pass', 'qa');
    }

    public function testGroupsAreReadableIfADefaultEnvIsDefined()
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

        $parser = new Parser(new Filesystem(new InMemory(array(self::MASTERFILE_PATH => $masterContent))));

        $parser->enableIncludeSupport()
            ->enableExternalSupport()
            ->enableGroupSupport();

        $variables = $parser->parse(self::MASTERFILE_PATH);
        $reader = new Reader($variables, $parser->getExternalVariables(), $parser->getGroups(), $parser->getDefaultEnvironmentsForGroups());
        $this->assertSame($reader->read('db.pass', 'qa'), $reader->read('db.pass', 'staging'));
    }

    public function testGroupsInDifferentFiles()
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

        $parser = new Parser(new Filesystem(new InMemory(array(
            self::MASTERFILE_PATH => $masterContent,
            'group.conf' => $groupContent,
        ))));

        $parser->enableIncludeSupport()
            ->enableExternalSupport()
            ->enableGroupSupport();

        $variables = $parser->parse(self::MASTERFILE_PATH);
        $reader = new Reader($variables, $parser->getExternalVariables(), $parser->getGroups());

        $this->assertSame('password', $reader->read('db.pass', 'staging'));
    }

    public function testGroupsUsageInExternalFile()
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

        $parser = new Parser(new Filesystem(new InMemory(array(
            self::MASTERFILE_PATH => $masterContent,
            'secured.conf' => $securedContent,
        ))));

        $parser->enableIncludeSupport()
            ->enableExternalSupport()
            ->enableGroupSupport();

        $variables = $parser->parse(self::MASTERFILE_PATH);
        $reader = new Reader($variables, $parser->getExternalVariables(), $parser->getGroups());

        $this->assertSame('success', $reader->read('db.pass', 'staging'));
    }

    /**
     * @dataProvider providerTestIsSystem
     */
    public function testIsSystem($variable, $expected)
    {
        $this->assertSame($expected, $this->reader->isSystem($variable));
    }

    public function providerTestIsSystem()
    {
        return array(
            array('gourdin', true),
            array('tva', true),
            array('debug', false),
            array('list.ok', false),
        );
    }
}
