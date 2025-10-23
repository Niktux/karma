<?php

declare(strict_types = 1);

namespace Karma;

use Gaufrette\Filesystem;
use Karma\Filesystem\Adapters\Memory;
use Karma\Configuration\Reader;
use PHPUnit\Framework\TestCase;

class RollbackTest extends TestCase
{
    private Filesystem
        $sourceFs;
    private Hydrator
        $rollback;

    protected function setUp(): void
    {
        $this->sourceFs = new Filesystem(new Memory());
        $targetFs = new Filesystem(new Memory());

        $this->write('a.php-dist', 'a');
        $this->write('a.php', 'a');
        $this->write('a.php~', 'old_a');

        $this->write('notDistFile.php', 'right');
        $this->write('notDistFile.php~', 'wrong');

        $this->write('orphan.php~', 'wrong');

        $this->write('orphan2.php', 'right');
        $this->write('orphan2.php~', 'wrong');

        $this->write('b.php-dist', 'b');
        $this->write('b.php', 'b');

        $this->write('c.php-dist', 'c');
        $this->write('c.php~', 'old_c');

        $this->write('d.php-dist2', 'd2');
        $this->write('d.php', 'd');
        $this->write('d.php~', 'old_d');

        $this->write('subdir/s.php-dist', 's');
        $this->write('subdir/s.php', 's');
        $this->write('subdir/s.php~', 'old_s');

        $reader = new Reader([], []);
        $this->rollback = new Hydrator($this->sourceFs, $targetFs, $reader, new Finder($this->sourceFs));
    }

    public function testRollback(): void
    {
        $this->rollback
            ->setSuffix('-dist')
            ->rollback();

        $shouldExists = [
            'a.php-dist' => 'a',
            'a.php' => 'old_a',
            'a.php~' => 'old_a',

            'notDistFile.php' => 'right', // not modified !
            'notDistFile.php~' => 'wrong',

            'orphan.php~' => 'wrong',

            'orphan2.php' => 'right',
            'orphan2.php~' => 'wrong',

            'b.php' => 'b',

            'c.php' => 'old_c',
            'c.php~' => 'old_c',

            'd.php-dist2' => 'd2',
            'd.php' => 'd',
            'd.php~' => 'old_d',

            'subdir/s.php' => 'old_s',
            'subdir/s.php~' => 'old_s',
        ];

        $shouldNotExists = ['orphan.php', 'orphan2.php-dist', 'b.php~', 'd.php-dist'];

        foreach($shouldExists as $f => $content)
        {
            self::assertTrue($this->sourceFs->has($f), "File $f should exists");
            self::assertSame($content, $this->sourceFs->read($f));
        }

        foreach($shouldNotExists as $f)
        {
            self::assertFalse($this->sourceFs->has($f), "File should not exists");
        }
    }

    public function testDryRun(): void
    {
        $this->rollback
            ->setSuffix('-dist')
            ->setDryRun()
            ->rollback();

        $shouldExists = [
            'a.php-dist' => 'a',
            'a.php' => 'a',
            'a.php~' => 'old_a',

            'notDistFile.php' => 'right',
            'notDistFile.php~' => 'wrong',

            'orphan.php~' => 'wrong',

            'orphan2.php' => 'right',
            'orphan2.php~' => 'wrong',

            'b.php' => 'b',

            'c.php~' => 'old_c',

            'd.php-dist2' => 'd2',
            'd.php' => 'd',
            'd.php~' => 'old_d',

            'subdir/s.php' => 's',
            'subdir/s.php~' => 'old_s',
        ];

        $shouldNotExists = ['orphan.php', 'b.php~', 'c.php', 'd.php-dist'];

        foreach($shouldExists as $f => $content)
        {
            self::assertTrue($this->sourceFs->has($f), "File $f should exists");
            self::assertSame($content, $this->sourceFs->read($f));
        }

        foreach($shouldNotExists as $f)
        {
            self::assertFalse($this->sourceFs->has($f), "File should not exists");
        }
    }

    private function write(string $name, ?string $content = null): void
    {
        $this->sourceFs->write($name, $content);
    }
}
