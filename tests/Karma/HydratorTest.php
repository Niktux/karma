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
        $this->fs->write('a.php', '');
        $this->fs->write('b.php-dist', '<%var%>');
        $this->fs->write('c.php', '<%var%>');
        $this->fs->write('d.php-dist', 'var');
        $this->fs->write('e.php-dist', '<%var %>');
        
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
        $this->fs->write('a.php', '');
        $this->fs->write('b.php-dist', '<%var%>');
        $this->fs->write('c.php', '<%var%>');
    
        $this->hydrator
            ->setDryRun()
            ->hydrate('dev');
    
        $this->assertFalse($this->fs->has('b.php'));
    }
}