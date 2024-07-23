<?php

declare(strict_types = 1);

namespace Karma\FormatterProviders;

use Karma\ProfileReader;
use Gaufrette\Filesystem;
use Gaufrette\Adapter\InMemory;
use Karma\Application;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ProfileProviderTest extends TestCase
{
    private function buildProvider(?string $profileContent = null, string $filename = Application::PROFILE_FILENAME): ProfileProvider
    {
        $files = [];

        if($profileContent !== null)
        {
            $files = [
                $filename => $profileContent,
            ];
        }

        $profile = new ProfileReader(
            new Filesystem(new InMemory($files))
        );

        return new ProfileProvider($profile);
    }

    public function testFormatter(): void
    {
        $yaml = <<<YAML
formatters:
  yaml:
    <true>: "true"
    <false>: "false"
    <null> : 0
defaultFormatter: yaml
YAML;
        $provider = $this->buildProvider($yaml);
        $fileExtension = 'yaml';

        self::assertTrue($provider->hasFormatter('yaml'), 'Yaml formatter must exist');
        self::assertFalse($provider->hasFormatter('php'), 'PHP formatter must not exist');
        self::assertSame($provider->formatter($fileExtension), $provider->formatter($fileExtension, 'yaml'));
    }

    #[DataProvider('providerTestFormatterSyntaxError')]
    public function testFormatterSyntaxError(string $yaml): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->buildProvider($yaml);
    }

    public static function providerTestFormatterSyntaxError(): array
    {
        return [
            [<<<YAML
formatters:
  yaml: foobar
YAML
            ],
            [<<<YAML
formatters: foobar
YAML
            ],
        ];
    }

    public function testFormatterByFileExtension(): void
    {
        $yaml = <<<YAML
formatters:
  f1:
    <true>: "true"
  f2   :
    <false>: "false"
  f3:
    <null> : 0
defaultFormatter: f2
fileExtensionFormatters:
  ini : f1
  yml : f2
  cfg : f3

YAML;
        $provider = $this->buildProvider($yaml);

        self::assertSame($provider->formatter(null, 'f1'), $provider->formatter('ini', null));
        self::assertSame($provider->formatter(null, 'f2'), $provider->formatter('yml', null));
        self::assertSame($provider->formatter(null, 'f3'), $provider->formatter('cfg', null));
        self::assertSame($provider->formatter(null, 'f2'), $provider->formatter('txt', null)); // default
        self::assertSame($provider->formatter(null, 'f3'), $provider->formatter('ini', 'f3'));
    }
}
