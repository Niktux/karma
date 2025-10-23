<?php

declare(strict_types = 1);

namespace Karma\Generator;

use Karma\Configuration\Parser;
use Gaufrette\Filesystem;
use Karma\Filesystem\Adapters\Memory;
use Karma\Application;
use Karma\Generator\NameTranslators\NullTranslator;
use Karma\Generator\NameTranslators\FilePrefixTranslator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class VariableProviderTest extends TestCase
{
    private VariableProvider
        $provider;

    protected function setUp(): void
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

        $parser = new Parser(new Filesystem(new Memory($files)));
        $parser->enableIncludeSupport()
            ->enableExternalSupport()
            ->parse(Application::DEFAULT_MASTER_FILE);

        $this->provider = new VariableProvider($parser);
    }

    private function assertSameArraysExceptOrder(array $expected, array $result): void
    {
        ksort($result);
        ksort($expected);

        self::assertSame($expected, $result);
    }

    #[DataProvider('providerTestGetAllVariables')]
    public function testGetAllVariables(NameTranslator $translator, array $expected): void
    {
        $this->provider->setNameTranslator($translator);
        $variables = $this->provider->allVariables();

        $this->assertSameArraysExceptOrder($expected, $variables);
    }

    public static function providerTestGetAllVariables(): array
    {
        return [
            [new NullTranslator(), [
                'pass' => 'pass',
                'logger.level' => 'logger.level'
            ]],
            [new FilePrefixTranslator(), [
                'pass' => 'db.pass',
                'logger.level' => 'logger.level',
            ]],
        ];
    }
}