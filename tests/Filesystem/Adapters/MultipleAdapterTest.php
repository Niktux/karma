<?php

declare(strict_types = 1);

namespace Karma\Filesystem\Adapters;

use Gaufrette\Adapter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MultipleAdapterTest extends TestCase
{
    private Adapter
        $multiple,
        $src,
        $lib;
        
    protected function setUp(): void
    {
        $this->src = new Memory([
            'wildHorses' => 'Jolly Jumper',
            'pony/unicorn.ext' => 'PONY PONY RUN RUN',
        ]);
        
        $this->lib = new Memory([
            'burger' => 'BURGER',
            'fat/burger' => 'Mac Julian',
            'more/fat/burger' => 'Mac Fau',
        ]);
        
        $this->multiple = new MultipleAdapter();
        $this->multiple->mount('src', $this->src);
        $this->multiple->mount('var/lib', $this->lib);
    }
    
    public function testMount(): void
    {
        self::assertFalse($this->multiple->exists('tmp/test'), 'tmp/test should not exist');
        
        $this->multiple->mount('tmp', new Memory(['test' => 42]));
        
        self::assertTrue($this->multiple->exists('tmp/test'), 'tmp/test should exist');
    }
    
    #[DataProvider('providerAllFiles')]
    public function testRead(string $adapterName, string $expectedPath, string $testedPath): void
    {
        $adapter = $adapterName === 'src' ? $this->src : $this->lib;
        
        if($adapter->exists($expectedPath))
        {
            self::assertSame(
                $adapter->read($expectedPath),
                $this->multiple->read($testedPath)
            );
        }
        else
        {
            self::assertSame($expectedPath, 'doesNotExist');
        }
    }
    
    public static function providerAllFiles(): array
    {
        return [
            ['src', 'wildHorses', 'src/wildHorses'],
            ['src', 'pony/unicorn.ext', 'src/pony/unicorn.ext'],
            ['src', 'doesNotExist', 'doesNotExist'],

            ['lib', 'burger', 'var/lib/burger'],
            ['lib', 'fat/burger', 'var/lib/fat/burger'],
            ['lib', 'more/fat/burger', 'var/lib/more/fat/burger'],
            ['lib', 'doesNotExist', 'doesNotExist'],
        ];
    }

    public function testWrite(): void
    {
        self::assertFalse($this->multiple->exists('var/lib/reply'));
        self::assertFalse($this->lib->exists('reply'));
        self::assertFalse($this->src->exists('reply'));
        
        $content = 'Always reply 42';
        $this->multiple->write('var/lib/reply', $content);
        
        self::assertTrue($this->multiple->exists('var/lib/reply'));
        self::assertTrue($this->lib->exists('reply'));
        self::assertFalse($this->src->exists('reply'));
        
        self::assertSame($content, $this->multiple->read('var/lib/reply'));
        self::assertSame($content, $this->lib->read('reply'));
    }
    
    #[DataProvider('providerAllFiles')]
    public function testExists(string $adapterName, string $expectedPath, string $testedPath): void
    {
        $adapter = $adapterName === 'src' ? $this->src : $this->lib;
        
        self::assertSame(
            $adapter->exists($expectedPath),
            $this->multiple->exists($testedPath)
        );
    }
    
    public function testKeys(): void
    {
        $expected = [
            'src/wildHorses', 'src/pony/unicorn.ext',
            'var/lib/burger', 'var/lib/fat/burger', 'var/lib/more/fat/burger'
        ];
        
        $result = $this->multiple->keys();
        
        self::assertCount(count($expected), $result);
        foreach($expected as $path)
        {
            self::assertContains($path, $result);
        }
    }
    
    #[DataProvider('providerAllFiles')]
    public function testMtime(string $adapterName, string $expectedPath, string $testedPath): void
    {
        $adapter = $adapterName === 'src' ? $this->src : $this->lib;
        
        if($adapter->exists($expectedPath))
        {
            self::assertSame(
                $adapter->mtime($expectedPath),
                $this->multiple->mtime($testedPath)
            );
        }
        else
        {
            self::assertSame($expectedPath, 'doesNotExist');
        }
    }
    
    public function testDelete(): void
    {
        self::assertTrue($this->multiple->exists('var/lib/fat/burger'));
        self::assertTrue($this->lib->exists('fat/burger'));
        
        $this->multiple->delete('var/lib/fat/burger');
        
        self::assertFalse($this->multiple->exists('var/lib/fat/burger'));
        self::assertFalse($this->lib->exists('fat/burger'));
    }
    
    public function testRename(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->multiple->rename('a', 'b');
    }
    
    #[DataProvider('providerAllFiles')]
    public function testIsDirectory(string $adapterName, string $expectedPath, string $testedPath): void
    {
        $adapter = $adapterName === 'src' ? $this->src : $this->lib;
        
        if($adapter->exists($expectedPath))
        {
            self::assertSame(
                $adapter->isDirectory($expectedPath),
                $this->multiple->isDirectory($testedPath)
            );
        }
        else
        {
            self::assertSame($expectedPath, 'doesNotExist');
        }
    }
}
