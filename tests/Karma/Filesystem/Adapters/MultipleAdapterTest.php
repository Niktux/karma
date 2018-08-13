<?php

namespace Karma\Filesystem\Adapters;

use Gaufrette\Adapter;
use Gaufrette\Adapter\InMemory;
use PHPUnit\Framework\TestCase;

class MultipleAdapterTest extends TestCase
{
    private
        $multiple,
        $src,
        $lib;
        
    protected function setUp()
    {
        $this->src = new InMemory([
            'wildHorses' => 'Jolly Jumper',
            'pony/unicorn.ext' => 'PONY PONY RUN RUN',
        ]);
        
        $this->lib = new InMemory([
            'burger' => 'BURGER',
            'fat/burger' => 'Mac Julian',
            'more/fat/burger' => 'Mac Fau',
        ]);
        
        $this->multiple = new MultipleAdapter();
        $this->multiple->mount('src', $this->src);
        $this->multiple->mount('var/lib', $this->lib);
    }
    
    public function testMount()
    {
        $this->assertFalse($this->multiple->exists('tmp/test'), 'tmp/test should not exist');
        
        $this->multiple->mount('tmp', new InMemory(['test' => 42]));
        
        $this->assertTrue($this->multiple->exists('tmp/test'), 'tmp/test should exist');
    }
    
    /**
     * @dataProvider providerAllFiles
     */
    public function testRead($adapterName, $expectedPath, $testedPath)
    {
        $adapter = $adapterName === 'src' ? $this->src : $this->lib;
        
        if($adapter->exists($expectedPath))
        {
            $this->assertSame(
                $adapter->read($expectedPath),
                $this->multiple->read($testedPath)
            );
        }
    }
    
    public function providerAllFiles()
    {
        return array(
            array('src', 'wildHorses', 'src/wildHorses'),
            array('src', 'pony/unicorn.ext', 'src/pony/unicorn.ext'),
            array('src', 'doesNotExist', 'src/doesNotExist'),
            
            array('lib', 'burger', 'var/lib/burger'),
            array('lib', 'fat/burger', 'var/lib/fat/burger'),
            array('lib', 'more/fat/burger', 'var/lib/more/fat/burger'),
            array('lib', 'doesNotExist', 'var/lib/doesNotExist'),
        );
    }
    
    public function testWrite()
    {
        $this->assertFalse($this->multiple->exists('var/lib/reply'));
        $this->assertFalse($this->lib->exists('reply'));
        $this->assertFalse($this->src->exists('reply'));
        
        $content = 'Always reply 42';
        $this->multiple->write('var/lib/reply', $content);
        
        $this->assertTrue($this->multiple->exists('var/lib/reply'));
        $this->assertTrue($this->lib->exists('reply'));
        $this->assertFalse($this->src->exists('reply'));
        
        $this->assertSame($content, $this->multiple->read('var/lib/reply'));
        $this->assertSame($content, $this->lib->read('reply'));
    }
    
    /**
     * @dataProvider providerAllFiles
     */
    public function testExists($adapterName, $expectedPath, $testedPath)
    {
        $adapter = $adapterName === 'src' ? $this->src : $this->lib;
        
        $this->assertSame(
            $adapter->exists($expectedPath),
            $this->multiple->exists($testedPath)
        );
    }
    
    public function testKeys()
    {
        $expected = [
            'src/wildHorses', 'src/pony/unicorn.ext',
            'var/lib/burger', 'var/lib/fat/burger', 'var/lib/more/fat/burger'
        ];
        
        $result = $this->multiple->keys();
        
        $this->assertCount(count($expected), $result);
        foreach($expected as $path)
        {
            $this->assertContains($path, $result);
        }
    }
    
    /**
     * @dataProvider providerAllFiles
     */
    public function testMtime($adapterName, $expectedPath, $testedPath)
    {
        $adapter = $adapterName === 'src' ? $this->src : $this->lib;
        
        if($adapter->exists($expectedPath))
        {
            $this->assertSame(
                $adapter->mtime($expectedPath),
                $this->multiple->mtime($testedPath)
            );
        }
    }
    
    public function testDelete()
    {
        $this->assertTrue($this->multiple->exists('var/lib/fat/burger'));
        $this->assertTrue($this->lib->exists('fat/burger'));
        
        $this->multiple->delete('var/lib/fat/burger');
        
        $this->assertFalse($this->multiple->exists('var/lib/fat/burger'));
        $this->assertFalse($this->lib->exists('fat/burger'));
    }
    
    /**
     * @expectedException \RuntimeException
     */
    public function testRename()
    {
        $this->multiple->rename('a', 'b');
    }
    
    /**
     * @dataProvider providerAllFiles
     */
    public function testIsDirectory($adapterName, $expectedPath, $testedPath)
    {
        $adapter = $adapterName === 'src' ? $this->src : $this->lib;
        
        if($adapter->exists($expectedPath))
        {
            $this->assertSame(
                $adapter->isDirectory($expectedPath),
                $this->multiple->isDirectory($testedPath)
            );
        }
    }
}
