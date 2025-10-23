<?php

declare(strict_types = 1);

namespace Karma;

use Gaufrette\Filesystem;
use Karma\Filesystem\Adapters\Memory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ProfileReaderTest extends TestCase
{
    private function buildReader(?string $profileContent = null, ?string $filename = Application::PROFILE_FILENAME): ProfileReader
    {
        $files = [];

        if($profileContent !== null)
        {
            $files = [
               $filename => $profileContent,
            ];
        }

        return new ProfileReader(
            new Filesystem(new Memory($files))
        );
    }

    #[DataProvider('providerTestEmpty')]
    public function testEmpty(?string $yaml, ?string $profileFilename): void
    {
        $reader = $this->buildReader($yaml, $profileFilename);

        self::assertFalse($reader->hasTemplatesSuffix());
        self::assertNull($reader->templatesSuffix());

        self::assertFalse($reader->hasMasterFilename());
        self::assertNull($reader->masterFilename());

        self::assertFalse($reader->hasConfigurationDirectory());
        self::assertNull($reader->configurationDirectory());
    }

    public static function providerTestEmpty(): array
    {
        return [
            'no profile' => [null, null],
            'invalid key' => ['suffixes: -tpl', null],
            'bad character case' => ['SUFFIX: -tpl', null],
            'bad profile filename' => ['suffix: -tpl', '.stuff'],
        ];
    }

    public function testMasterOnly(): void
    {
        $yaml = <<<YAML
master: othermaster.conf
YAML;

        $reader = $this->buildReader($yaml);

        self::assertFalse($reader->hasTemplatesSuffix());
        self::assertNull($reader->templatesSuffix());

        self::assertTrue($reader->hasMasterFilename());
        self::assertSame('othermaster.conf', $reader->masterFilename());

        self::assertFalse($reader->hasConfigurationDirectory());
        self::assertNull($reader->configurationDirectory());
    }

    public function testSuffixOnly(): void
    {
        $yaml = <<<YAML
suffix: -tpl
YAML;

        $reader = $this->buildReader($yaml);

        self::assertTrue($reader->hasTemplatesSuffix());
        self::assertSame('-tpl', $reader->templatesSuffix());

        self::assertFalse($reader->hasMasterFilename());
        self::assertNull($reader->masterFilename());

        self::assertFalse($reader->hasConfigurationDirectory());
        self::assertNull($reader->configurationDirectory());
    }

    public function testFullProfile(): void
    {
        $yaml = <<<YAML
suffix: -tpl
master: othermaster.conf
confDir: env2/
sourcePath: lib/
targetPath: target/
generator:
  translator: prefix
YAML;

        $reader = $this->buildReader($yaml);

        self::assertTrue($reader->hasTemplatesSuffix());
        self::assertSame('-tpl', $reader->templatesSuffix());

        self::assertTrue($reader->hasMasterFilename());
        self::assertSame('othermaster.conf', $reader->masterFilename());

        self::assertTrue($reader->hasConfigurationDirectory());
        self::assertSame('env2/', $reader->configurationDirectory());

        self::assertTrue($reader->hasSourcePath());
        self::assertSame('lib/', $reader->sourcePath());

        self::assertTrue($reader->hasTargetPath());
        self::assertSame('target/', $reader->targetPath());

        self::assertSame(['translator' => 'prefix'], $reader->generatorOptions());
    }

    public function testSourcePathAsArray(): void
    {
        $yaml = <<<YAML
suffix: -tpl
master: othermaster.conf
confDir: env2/
sourcePath:
    - lib/
    - config/
    - settings/
generator:
  translator: prefix
YAML;

        $reader = $this->buildReader($yaml);

        self::assertTrue($reader->hasSourcePath());
        $srcPath = $reader->sourcePath();
        self::assertIsArray($srcPath);
        self::assertCount(3, $srcPath);
        
        self::assertContains('lib/', $srcPath);
        self::assertContains('config/', $srcPath);
        self::assertContains('settings/', $srcPath);

        self::assertSame(array('translator' => 'prefix'), $reader->generatorOptions());
    }

    public function testSyntaxError(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->buildReader("\tsuffix:-tpl");
    }

    public function testTargetPathAsArray(): void
    {
        $this->expectException(\RuntimeException::class);

        $yaml = <<<YAML
targetPath:
    - path1/
    - path2/
YAML;

        $this->buildReader($yaml);
    }

    public function testInvalidFormat(): void
    {
        $reader = $this->buildReader( <<< YAML
suffix:
  - tpl
  - dist
master:
  - othermaster.conf
  - othermaster2.conf
confDir:
  - env2/
  - env3/
sourcePath:
  - lib/
  - src/
defaultFormatter:
  - 1
  - 2
fileExtensionFormatters:
  1 : foo
  2 : bar
YAML
        );

        self::assertFalse($reader->hasTemplatesSuffix(), 'Template suffix must only allow strings');
        self::assertFalse($reader->hasMasterFilename(), 'Master file name must only allow strings');
        self::assertFalse($reader->hasConfigurationDirectory(), 'confDir must only allow strings');
        self::assertTrue($reader->hasSourcePath(), 'sourcePath must allow both strings and arrays');

        self::assertIsNotArray($reader->defaultFormatterName(), 'Default formatter must only allow strings');

        self::assertIsArray($reader->fileExtensionFormatters());
    }
}
