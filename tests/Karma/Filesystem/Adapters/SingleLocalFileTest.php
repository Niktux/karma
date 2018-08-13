<?php

namespace Karma\Filesystem\Adapters;

use Gaufrette\Adapter\InMemory;
use PHPUnit\Framework\TestCase;

class SingleLocalFileTest extends TestCase
{
    const
        FILENAME = 'toto.yml-dist',
        FILEDIR = '/path/to/',
        FILEPATH = '/path/to/toto.yml-dist',
        CONTENT = 'yolo';

    private
        $src,
        $singleLocalFile;

    protected function setUp()
    {
        $this->src = new InMemory([
            self::FILENAME => self::CONTENT,
            'subDir/anotherFile' => 'yala',
            'fileInSameDir' => 'yili',
        ]);

        $this->singleLocalFile = new SingleLocalFile(self::FILENAME, $this->src);
    }

    /**
     * @dataProvider providerTestRead
     */
    public function testRead($key, $expected)
    {
        $this->assertSame($expected, $this->singleLocalFile->read($key));
    }

    public function providerTestRead()
    {
        return array(
            [self::FILENAME, self::CONTENT],
            [self::FILEPATH, false],
            ['/path/to/subDir/anotherFile', false],
            ['/path/to/fileInSameDir', false],
            ['fileInSameDir', false],
            ['doesNotExist', false],
        );
    }

    /**
    * @dataProvider providerTestWrite
    */
    public function testWrite($key, $expected)
    {
        $content = 'burger';

        $this->assertFalse($this->singleLocalFile->read($key), 'precond fail');
        $this->singleLocalFile->write($key, $content);
        $this->assertSame($expected, $this->src->exists($key), 'written as expected');

        if($expected === true)
        {
            $this->assertSame($content, $this->src->read($key));
        }

        $this->assertFalse($this->singleLocalFile->read($key));
    }

    public function providerTestWrite()
    {
        return array(
            ['newFile', true],
            ['new/sub/dirs/newFile', true],
        );
    }

    /**
     * @dataProvider providerTestRead
     */
    public function testExists($key, $expected)
    {
        $this->assertSame(
            $expected !== false,
            $this->singleLocalFile->exists($key)
        );
    }

    public function testKeys()
    {
        $keys = $this->singleLocalFile->keys();

        $this->assertCount(1, $keys);
        $this->assertContains(self::FILENAME, $keys);
        $this->assertNotContains(self::FILEPATH, $keys);
    }

    public function testMtime()
    {
        $this->assertSame(
            $this->singleLocalFile->mtime(self::FILENAME),
            $this->src->mtime(self::FILENAME)
        );

        $this->assertFalse($this->singleLocalFile->mtime('fileInSameDir'));
    }

    /**
     * @dataProvider providerFileList
     * @expectedException \RuntimeException
     */
    public function testDelete($key)
    {
        $this->singleLocalFile->delete($key);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testRename()
    {
        $this->singleLocalFile->rename(self::FILENAME, 'renamed');
    }

    /**
     * @dataProvider providerFileList
     */
    public function testIsDirectory($key)
    {
        $this->assertFalse($this->singleLocalFile->isDirectory($key));
    }

    public function providerFileList()
    {
        return array(
            [self::FILEPATH],
            ['fileInSameDir'],
            [self::FILEDIR . 'fileInSameDir'],
            ['doesNotExist'],
            [self::FILEDIR . 'doesNotExist'],
            ['anotherFile'],
        );
    }
}
