<?php

declare(strict_types = 1);

namespace Karma;

use Gaufrette\Filesystem;
use Gaufrette\Adapter\InMemory;
use PHPUnit\Framework\TestCase;

class ProfileReaderTest extends TestCase
{
    private function buildReader($profileContent = null, $filename = Application::PROFILE_FILENAME)
    {
        $files = array();

        if($profileContent !== null)
        {
            $files = array(
               $filename => $profileContent,
            );
        }

        return new ProfileReader(
            new Filesystem(new InMemory($files))
        );
    }

    /**
     * @dataProvider providerTestEmpty
     */
    public function testEmpty($yaml, $profileFilename)
    {
        $profile = Application::PROFILE_FILENAME;
        if($profileFilename !== null)
        {
            $profile = $profileFilename;
        }

        $reader = $this->buildReader($yaml, $profileFilename);

        self::assertFalse($reader->hasTemplatesSuffix());
        self::assertNull($reader->getTemplatesSuffix());

        self::assertFalse($reader->hasMasterFilename());
        self::assertNull($reader->getMasterFilename());

        self::assertFalse($reader->hasConfigurationDirectory());
        self::assertNull($reader->getConfigurationDirectory());
    }

    public function providerTestEmpty()
    {
        return array(
            'no profile' => array(null, null),
            'invalid key' => array('suffixes: -tpl', null),
            'bad character case' => array('SUFFIX: -tpl', null),
            'bad profile filename' => array('suffix: -tpl', '.stuff'),
        );
    }

    public function testMasterOnly()
    {
        $yaml = <<<YAML
master: othermaster.conf
YAML;

        $reader = $this->buildReader($yaml);

        self::assertFalse($reader->hasTemplatesSuffix());
        self::assertNull($reader->getTemplatesSuffix());

        self::assertTrue($reader->hasMasterFilename());
        self::assertSame('othermaster.conf', $reader->getMasterFilename());

        self::assertFalse($reader->hasConfigurationDirectory());
        self::assertNull($reader->getConfigurationDirectory());
    }

    public function testSuffixOnly()
    {
        $yaml = <<<YAML
suffix: -tpl
YAML;

        $reader = $this->buildReader($yaml);

        self::assertTrue($reader->hasTemplatesSuffix());
        self::assertSame('-tpl', $reader->getTemplatesSuffix());

        self::assertFalse($reader->hasMasterFilename());
        self::assertNull($reader->getMasterFilename());

        self::assertFalse($reader->hasConfigurationDirectory());
        self::assertNull($reader->getConfigurationDirectory());
    }

    public function testFullProfile()
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
        self::assertSame('-tpl', $reader->getTemplatesSuffix());

        self::assertTrue($reader->hasMasterFilename());
        self::assertSame('othermaster.conf', $reader->getMasterFilename());

        self::assertTrue($reader->hasConfigurationDirectory());
        self::assertSame('env2/', $reader->getConfigurationDirectory());

        self::assertTrue($reader->hasSourcePath());
        self::assertSame('lib/', $reader->getSourcePath());

        self::assertTrue($reader->hasTargetPath());
        self::assertSame('target/', $reader->getTargetPath());

        self::assertSame(array('translator' => 'prefix'), $reader->getGeneratorOptions());
    }

    public function testSourcePathAsArray()
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
        $srcPath = $reader->getSourcePath();
        self::assertIsArray($srcPath);
        self::assertCount(3, $srcPath);
        
        self::assertContains('lib/', $srcPath);
        self::assertContains('config/', $srcPath);
        self::assertContains('settings/', $srcPath);

        self::assertSame(array('translator' => 'prefix'), $reader->getGeneratorOptions());
    }

    public function testSyntaxError()
    {
        $this->expectException(\RuntimeException::class);

        $this->buildReader("\tsuffix:-tpl");
    }

    public function testTargetPathAsArray()
    {
        $this->expectException(\RuntimeException::class);

        $yaml = <<<YAML
targetPath:
    - path1/
    - path2/
YAML;

        $reader = $this->buildReader($yaml);
    }

    public function testInvalidFormat()
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

        self::assertFalse(is_array($reader->getDefaultFormatterName()), 'Default formatter must only allow strings');

        self::assertTrue(is_array($reader->getFileExtensionFormatters()));
    }
}
