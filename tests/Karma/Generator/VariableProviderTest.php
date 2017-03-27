<?php

namespace Karma\Generator;

use Karma\Configuration\Parser;
use Gaufrette\Filesystem;
use Gaufrette\Adapter\InMemory;
use Karma\Application;
use Karma\Generator\NameTranslators\NullTranslator;
use Karma\Generator\NameTranslators\FilePrefixTranslator;

class VariableProviderTest extends \PHPUnit_Framework_TestCase
{
    private
        $provider;

    protected function setUp()
    {
        $masterContent = <<<CONFFILE
[externals]
external.conf

[includes]
db.conf

[variables]
logger.level:
    staging = info
    default = warning
CONFFILE;

        $externalContent = <<<CONFFILE
[variables]
# db.conf
pass:
    prod = veryComplexPass
CONFFILE;

        $dbContent = <<<CONFFILE
[variables]
pass:
    dev = 1234
    prod = <external>
    default = root
CONFFILE;

        $files = array(
            Application::DEFAULT_MASTER_FILE => $masterContent,
            'external.conf' => $externalContent,
            'db.conf' => $dbContent,
        );

        $parser = new Parser(new Filesystem(new InMemory($files)));
        $parser->enableIncludeSupport()
            ->enableExternalSupport()
            ->parse(Application::DEFAULT_MASTER_FILE);

        $this->provider = new VariableProvider($parser);
    }

    private function assertSameArraysExceptOrder($expected, $result)
    {
        ksort($result);
        ksort($expected);

        $this->assertSame($expected, $result);
    }

    /**
     * @dataProvider providerTestGetAllVariables
     */
    public function testGetAllVariables($translator, $expected)
    {
        $this->provider->setNameTranslator($translator);
        $variables = $this->provider->getAllVariables();

        $this->assertSameArraysExceptOrder($expected, $variables);
    }

    public function providerTestGetAllVariables()
    {
        return array(
            array(new NullTranslator(), array(
                'pass' => 'pass',
                'logger.level' => 'logger.level'
            )),
            array(new FilePrefixTranslator(), array(
                'pass' => 'db.pass',
                'logger.level' => 'logger.level',
            )),
        );
    }
}