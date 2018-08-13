<?php

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

        $this->assertFalse($reader->hasTemplatesSuffix());
        $this->assertNull($reader->getTemplatesSuffix());

        $this->assertFalse($reader->hasMasterFilename());
        $this->assertNull($reader->getMasterFilename());

        $this->assertFalse($reader->hasConfigurationDirectory());
        $this->assertNull($reader->getConfigurationDirectory());
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

        $this->assertFalse($reader->hasTemplatesSuffix());
        $this->assertNull($reader->getTemplatesSuffix());

        $this->assertTrue($reader->hasMasterFilename());
        $this->assertSame('othermaster.conf', $reader->getMasterFilename());

        $this->assertFalse($reader->hasConfigurationDirectory());
        $this->assertNull($reader->getConfigurationDirectory());
    }

    public function testSuffixOnly()
    {
        $yaml = <<<YAML
suffix: -tpl
YAML;

        $reader = $this->buildReader($yaml);

        $this->assertTrue($reader->hasTemplatesSuffix());
        $this->assertSame('-tpl', $reader->getTemplatesSuffix());

        $this->assertFalse($reader->hasMasterFilename());
        $this->assertNull($reader->getMasterFilename());

        $this->assertFalse($reader->hasConfigurationDirectory());
        $this->assertNull($reader->getConfigurationDirectory());
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

        $this->assertTrue($reader->hasTemplatesSuffix());
        $this->assertSame('-tpl', $reader->getTemplatesSuffix());

        $this->assertTrue($reader->hasMasterFilename());
        $this->assertSame('othermaster.conf', $reader->getMasterFilename());

        $this->assertTrue($reader->hasConfigurationDirectory());
        $this->assertSame('env2/', $reader->getConfigurationDirectory());

        $this->assertTrue($reader->hasSourcePath());
        $this->assertSame('lib/', $reader->getSourcePath());

        $this->assertTrue($reader->hasTargetPath());
        $this->assertSame('target/', $reader->getTargetPath());

        $this->assertSame(array('translator' => 'prefix'), $reader->getGeneratorOptions());
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

        $this->assertTrue($reader->hasSourcePath());
        $srcPath = $reader->getSourcePath();
        $this->assertInternalType('array', $srcPath);
        $this->assertCount(3, $srcPath);
        
        $this->assertContains('lib/', $srcPath);
        $this->assertContains('config/', $srcPath);
        $this->assertContains('settings/', $srcPath);

        $this->assertSame(array('translator' => 'prefix'), $reader->getGeneratorOptions());
    }

    /**
     * @expectedException RuntimeException
     */
    public function testSyntaxError()
    {
        $this->buildReader("\tsuffix:-tpl");
    }

    /**
     * @expectedException RuntimeException
     */
    public function testTargetPathAsArray()
    {
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

        $this->assertFalse($reader->hasTemplatesSuffix(), 'Template suffix must only allow strings');
        $this->assertFalse($reader->hasMasterFilename(), 'Master file name must only allow strings');
        $this->assertFalse($reader->hasConfigurationDirectory(), 'confDir must only allow strings');
        $this->assertTrue($reader->hasSourcePath(), 'sourcePath must allow both strings and arrays');

        $this->assertFalse(is_array($reader->getDefaultFormatterName()), 'Default formatter must only allow strings');

        $this->assertTrue(is_array($reader->getFileExtensionFormatters()));
    }
}
