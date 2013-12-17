<?php

use Symfony\Component\Console\Application;
use Karma\Command\Hydrate;
use Symfony\Component\Console\Tester\CommandTester;
use Karma\Hydrator;
use Gaufrette\Filesystem;
use Gaufrette\Adapter\InMemory;
use Karma\FakeReader;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class HydrateTest extends PHPUnit_Framework_TestCase
{
    private
        $fs,
        $hydrator;
    
    protected function setUp()
    {
        $this->fs = new Filesystem(new InMemory());
        $suffix = '-dist';
        $reader = new FakeReader(array(
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
        
        $this->hydrator->hydrate($environment);
        
        $this->assertTrue($this->fs->has('b.php'));
        $this->assertSame($expectedBValue, $this->fs->read('b.php'));
        $this->assertSame('<%var%>', $this->fs->read('c.php'));
    }
    
    public function providerTestSimple()
    {
        return array(
            array('dev', '42'),
            array('preprod', '51'),
        );
    }
}