<?php

namespace Karma\Generator;

use Karma\Configuration\Parser;
use Gaufrette\Filesystem;
use Gaufrette\Adapter\InMemory;
use Karma\Application;
use Karma\Configuration\Reader;
use Karma\Generator\NameTranslators\NullTranslator;

class VariableProviderTest extends \PHPUnit_Framework_TestCase
{
    private
        $provider;

    protected function setUp()
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
            Application::DEFAULT_MASTER_FILE => $masterContent,
            'external1.conf' => $externalContent1,
            'external2.conf' => $externalContent2,
        );

        $parser = new Parser(new Filesystem(new InMemory($files)));

        $parser->enableIncludeSupport()
            ->enableExternalSupport();

        $this->provider = new VariableProvider($parser, Application::DEFAULT_MASTER_FILE);
    }

    private function assertSameArraysExceptOrder($expected, $result)
    {
        ksort($result);
        ksort($expected);

        $this->assertSame($expected, $result);
    }

    public function testGetAllVariables()
    {
        $this->provider->setNameTranslator(new NullTranslator());

        $variables = $this->provider->getAllVariables();
        $expected = array(
            'db.pass', 'db.user',
        );

        $this->assertSameArraysExceptOrder($expected, $variables);
    }
}