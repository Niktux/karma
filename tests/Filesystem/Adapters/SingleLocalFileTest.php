<?php

declare(strict_types = 1);

namespace Karma\Filesystem\Adapters;

use Gaufrette\Adapter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SingleLocalFileTest extends TestCase
{
    private const
        FILENAME = 'toto.yml-dist',
        FILEDIR = '/path/to/',
        FILEPATH = '/path/to/toto.yml-dist',
        CONTENT = 'yolo';

    private Adapter
        $src;
    private SingleLocalFile
        $singleLocalFile;

    protected function setUp(): void
    {
        $this->src = new Memory([
            self::FILENAME => self::CONTENT,
            'subDir/anotherFile' => 'yala',
            'fileInSameDir' => 'yili',
        ]);

        $this->singleLocalFile = new SingleLocalFile(self::FILENAME, $this->src);
    }

    #[DataProvider('providerTestRead')]
    public function testRead(string $key, $expected): void
    {
        self::assertSame($expected, $this->singleLocalFile->read($key));
    }

    public static function providerTestRead(): array
    {
        return [
            [self::FILENAME, self::CONTENT],
            [self::FILEPATH, false],
            ['/path/to/subDir/anotherFile', false],
            ['/path/to/fileInSameDir', false],
            ['fileInSameDir', false],
            ['doesNotExist', false],
        ];
    }

    #[DataProvider('providerTestWrite')]
    public function testWrite(string $key, $expected): void
    {
        $content = 'burger';

        self::assertFalse($this->singleLocalFile->read($key), 'precond fail');
        $this->singleLocalFile->write($key, $content);
        self::assertSame($expected, $this->src->exists($key), 'written as expected');

        if($expected === true)
        {
            self::assertSame($content, $this->src->read($key));
        }

        self::assertFalse($this->singleLocalFile->read($key));
    }

    public static function providerTestWrite(): array
    {
        return [
            ['newFile', true],
            ['new/sub/dirs/newFile', true],
        ];
    }

    #[DataProvider('providerTestRead')]
    public function testExists(string $key, $expected): void
    {
        self::assertSame(
            $expected !== false,
            $this->singleLocalFile->exists($key)
        );
    }

    public function testKeys(): void
    {
        $keys = $this->singleLocalFile->keys();

        self::assertCount(1, $keys);
        self::assertContains(self::FILENAME, $keys);
        self::assertNotContains(self::FILEPATH, $keys);
    }

    public function testMtime(): void
    {
        self::assertSame(
            $this->singleLocalFile->mtime(self::FILENAME),
            $this->src->mtime(self::FILENAME)
        );

        self::assertFalse($this->singleLocalFile->mtime('fileInSameDir'));
    }

    #[DataProvider('providerFileList')]
    public function testDelete(string $key): void
    {
        $this->expectException(\RuntimeException::class);

        $this->singleLocalFile->delete($key);
    }

    public function testRename(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->singleLocalFile->rename(self::FILENAME, 'renamed');
    }

    #[DataProvider('providerFileList')]
    public function testIsDirectory(string $key): void
    {
        self::assertFalse($this->singleLocalFile->isDirectory($key));
    }

    public static function providerFileList(): array
    {
        return [
            [self::FILEPATH],
            ['fileInSameDir'],
            [self::FILEDIR . 'fileInSameDir'],
            ['doesNotExist'],
            [self::FILEDIR . 'doesNotExist'],
            ['anotherFile'],
        ];
    }
}
