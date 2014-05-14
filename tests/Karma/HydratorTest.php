<?php

use Gaufrette\Filesystem;
use Gaufrette\Adapter\InMemory;
use Karma\Hydrator;
use Karma\Configuration\InMemoryReader;
use Karma\Finder;
use Karma\ProfileReader;
use Karma\FormatterProviders\NullProvider;
use Karma\FormatterProviders\CallbackProvider;
use Karma\Formatters\Rules;

class HydratorTest extends PHPUnit_Framework_TestCase
{
    private
        $fs,
        $hydrator;
    
    protected function setUp()
    {
        $this->fs = new Filesystem(new InMemory());
        $reader = new InMemoryReader(array(
            'var:dev' => 42,
            'var:preprod' => 51,
            'var:prod' => 69,
            'db.user:dev' => 'root',
            'db.user:preprod' => 'someUser',
            'bool:dev' => true,
            'bool:prod' => false,
        ));
        
        $this->hydrator = new Hydrator($this->fs, $reader, new Finder($this->fs), new NullProvider());
        $this->hydrator->setSuffix('-dist');
    }
    
    /**
     * @dataProvider providerTestSimple
     */
    public function testSimple($environment, $expectedBValue, $expectedFValue)
    {
        $this->write('a.php');
        $this->write('b.php-dist', '<%var%>');
        $this->write('c.php', '<%var%>');
        $this->write('d.php-dist', 'var');
        $this->write('e.php-dist', '<%var %>');
        $this->write('f.php-dist', '<%db.user%>');
        
        $this->hydrator->hydrate($environment);
        
        $this->assertTrue($this->fs->has('b.php'));
        $this->assertTrue($this->fs->has('d.php'));
        $this->assertTrue($this->fs->has('e.php'));
        $this->assertTrue($this->fs->has('f.php'));
        
        $this->assertSame($expectedBValue, $this->fs->read('b.php'));
        
        $this->assertSame('<%var%>', $this->fs->read('c.php'));
        $this->assertSame('var', $this->fs->read('d.php'));
        $this->assertSame('<%var %>', $this->fs->read('e.php'));
        $this->assertSame($expectedFValue, $this->fs->read('f.php'));
    }
    
    public function providerTestSimple()
    {
        return array(
            array('dev', '42', 'root'),
            array('preprod', '51', 'someUser'),
        );
    }
    
    public function testDryRun()
    {
        $this->write('a.php');
        $this->write('b.php-dist', '<%var%>');
        $this->write('c.php', '<%var%>');
    
        $this->hydrator
            ->setDryRun()
            ->hydrate('dev');
    
        $this->assertFalse($this->fs->has('b.php'));
    }
    
    public function testTrappedFilenames()
    {
        $existingFiles = array('a.php', 'b.php-dist', 'c.php-dis', 'd.php-distt', 'e.php-dist.dist', 'f.dist', 'g-dist.php', 'h.php-dist-dist');
        
        foreach($existingFiles as $file)
        {
            $this->write($file);
        }
        
        $this->hydrator->hydrate('prod');
        
        $createdFiles = array('b.php', 'h.php-dist');
        $allFiles = array_merge($existingFiles, $createdFiles);

        // check there is no extra generated file
        $this->assertSame(count($allFiles), count($this->fs->keys()));
        
        foreach($allFiles as $file)
        {
            $this->assertTrue($this->fs->has($file), "File $file should be created");
        }
    }
    
    private function write($name, $content = null)
    {
        $this->fs->write($name, $content);
    }

    public function testBackupFiles()
    {
        $this->write('a.php-dist');
        $this->write('b.php-dist', '<%var%>');
        $this->write('b.php', 'oldValue');
        $this->write('c.php-dist');
        
        $this->hydrator
            ->enableBackup()
            ->hydrate('dev');
        
        $this->assertTrue($this->fs->has('a.php'));
        $this->assertFalse($this->fs->has('a.php~'));
        
        $this->assertTrue($this->fs->has('b.php'));
        $this->assertTrue($this->fs->has('b.php~'));
        
        $this->assertTrue($this->fs->has('c.php'));
        $this->assertFalse($this->fs->has('c.php~'));
        
        $this->assertSame('42', $this->fs->read('b.php'));
        $this->assertSame('oldValue', $this->fs->read('b.php~'));
        
        $this->hydrator->hydrate('dev');
        
        $this->assertTrue($this->fs->has('a.php~'));
        $this->assertTrue($this->fs->has('b.php~'));
        $this->assertTrue($this->fs->has('c.php~'));
        
        $this->assertSame('42', $this->fs->read('b.php~'));
    }
    
    public function testFormatter()
    {
        $formatter = new Rules(array(
        	'<true>' => 'string_true',
        	'<false>' => 0,
        ));
        
        $provider = new CallbackProvider(function ($index) use($formatter) {
        	return $formatter;
        });
        
        $this->hydrator->setFormatterProvider($provider);
        
        $this->write('a.php-dist', '<%bool%>');
        
        $this->hydrator->hydrate('dev');
        $this->assertSame('string_true', $this->fs->read('a.php'));
        
        $this->hydrator->hydrate('prod');
        $this->assertSame('0', $this->fs->read('a.php'));
    }
}