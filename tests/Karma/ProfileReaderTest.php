<?php

use Karma\ProfileReader;
use Gaufrette\Filesystem;
use Gaufrette\Adapter\InMemory;
use Karma\Application;
use Gaufrette\Adapter;

class ProfileReaderTest extends PHPUnit_Framework_TestCase
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
    }
    
    /**
     * @expectedException RuntimeException
     */
    public function testSyntaxError()
    {
        $this->buildReader("\tsuffix:-tpl");
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
        $reader = $this->buildReader($yaml);
        $fileExtension = 'yaml';
        
        $this->assertTrue($reader->hasFormatter('yaml'), 'Yaml formatter must exist');
        $this->assertFalse($reader->hasFormatter('php'), 'PHP formatter must not exist');
        $this->assertInstanceOf('Karma\Formatter', $reader->getFormatter($fileExtension)); // default
        $this->assertInstanceOf('Karma\Formatter', $reader->getFormatter($fileExtension, 'yaml'));
        $this->assertSame($reader->getFormatter($fileExtension), $reader->getFormatter($fileExtension, 'yaml'));
    }
    
    /**
     * @dataProvider providerTestFormatterSyntaxError
     * @expectedException \InvalidArgumentException
     */
    public function testFormatterSyntaxError($yaml)
    {
        $reader = $this->buildReader($yaml);        
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
  f2:
    <false>: "false"
  f3:
    <null> : 0
defaultFormatter: f2
fileExtensionFormatters:
  ini : f1
  yml : f2
  cfg : f3

YAML;
        $reader = $this->buildReader($yaml);
    
        $this->assertSame($reader->getFormatter(null, 'f1'), $reader->getFormatter('ini', null));
        $this->assertSame($reader->getFormatter(null, 'f2'), $reader->getFormatter('yml', null));
        $this->assertSame($reader->getFormatter(null, 'f3'), $reader->getFormatter('cfg', null));
        $this->assertSame($reader->getFormatter(null, 'f2'), $reader->getFormatter('txt', null)); // default
        $this->assertSame($reader->getFormatter(null, 'f3'), $reader->getFormatter('ini', 'f3'));
    }
}