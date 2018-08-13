<?php

namespace Karma\FormatterProviders;

use Karma\ProfileReader;
use Gaufrette\Filesystem;
use Gaufrette\Adapter\InMemory;
use Karma\Application;
use PHPUnit\Framework\TestCase;

class ProfileProviderTest extends TestCase
{
    private function buildProvider($profileContent = null, $filename = Application::PROFILE_FILENAME)
    {
        $files = array();

        if($profileContent !== null)
        {
            $files = array(
                $filename => $profileContent,
            );
        }

        $profile = new ProfileReader(
            new Filesystem(new InMemory($files))
        );

        return new ProfileProvider($profile);
    }

    public function testFormatter()
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

        $this->assertTrue($provider->hasFormatter('yaml'), 'Yaml formatter must exist');
        $this->assertFalse($provider->hasFormatter('php'), 'PHP formatter must not exist');
        $this->assertInstanceOf('Karma\Formatter', $provider->getFormatter($fileExtension)); // default
        $this->assertInstanceOf('Karma\Formatter', $provider->getFormatter($fileExtension, 'yaml'));
        $this->assertSame($provider->getFormatter($fileExtension), $provider->getFormatter($fileExtension, 'yaml'));
    }

    /**
     * @dataProvider providerTestFormatterSyntaxError
     * @expectedException \InvalidArgumentException
     */
    public function testFormatterSyntaxError($yaml)
    {
        $provider = $this->buildProvider($yaml);
    }

    public function providerTestFormatterSyntaxError()
    {
        return array(
            array(<<<YAML
formatters:
  yaml: foobar
YAML
            ),
            array(<<<YAML
formatters: foobar
YAML
            ),
        );
    }

    public function testFormatterByFileExtension()
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

        $this->assertSame($provider->getFormatter(null, 'f1'), $provider->getFormatter('ini', null));
        $this->assertSame($provider->getFormatter(null, 'f2'), $provider->getFormatter('yml', null));
        $this->assertSame($provider->getFormatter(null, 'f3'), $provider->getFormatter('cfg', null));
        $this->assertSame($provider->getFormatter(null, 'f2'), $provider->getFormatter('txt', null)); // default
        $this->assertSame($provider->getFormatter(null, 'f3'), $provider->getFormatter('ini', 'f3'));
    }
}
