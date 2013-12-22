<?php

use Gaufrette\Filesystem;
use Gaufrette\Adapter\InMemory;
use Karma\Hydrator;
use Karma\Configuration\InMemoryReader;

class HydratorTest extends PHPUnit_Framework_TestCase
{
    private
        $fs,
        $hydrator;
    
    protected function setUp()
    {
        $this->fs = new Filesystem(new InMemory());
        $suffix = '-dist';
        $reader = new InMemoryReader(array(
            'var:dev' => 42,
            'var:preprod' => 51,
            'var:prod' => 69,
        ));
        
        $this->hydrator = new Hydrator($this->fs, $suffix, $reader);
    }
    
    /**
     * @dataProvider providerTestSimple
     */
    public function testSimple($environment, $expectedBValue)
    {
        $this->write('a.php');
        $this->write('b.php-dist', '<%var%>');
        $this->write('c.php', '<%var%>');
        $this->write('d.php-dist', 'var');
        $this->write('e.php-dist', '<%var %>');
        
        $this->hydrator->hydrate($environment);
        
        $this->assertTrue($this->fs->has('b.php'));
        $this->assertTrue($this->fs->has('d.php'));
        $this->assertTrue($this->fs->has('e.php'));
        
        $this->assertSame($expectedBValue, $this->fs->read('b.php'));
        
        $this->assertSame('<%var%>', $this->fs->read('c.php'));
        $this->assertSame('var', $this->fs->read('d.php'));
        $this->assertSame('<%var %>', $this->fs->read('e.php'));
    }
    
    public function providerTestSimple()
    {
        return array(
            array('dev', '42'),
            array('preprod', '51'),
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
}