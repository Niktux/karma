<?php

declare(strict_types = 1);

namespace Karma\Configuration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ValueFilterIteratorTest extends TestCase
{
    private \Iterator
        $values;

    protected function setUp(): void
    {
        $this->values = new \ArrayIterator(array(
            'db.user' => 'root',
            'db.pass' => 'rootroot',
            'db.host' => '192.160.13.12',
            'email' => 'root@db.org',
            'display_errors' => true,
            'trueString' => 'true',
            'someString' => 'Connecting as root is evil ! Mike12',
            'otherString' => 'null',
            'number' => 0,
            'tenNumber' => 10,
            'numberAsString' => '10',
            'stringWithStar' => 'b*te',
            'nullValue' => null,
            'list' => array(100, 'arootb', true, 'goo'),
        ));
    }

    #[DataProvider('providerTestFilter')]
    public function testFilter($filter, $expected): void
    {
        $it = new ValueFilterIterator($filter, $this->values);

        self::assertSame($expected, iterator_to_array($it));
    }

    public static function providerTestFilter(): array
    {
        return [
            ['root', [
                'db.user' => 'root',
            ]],
            ['root*', [
                'db.user' => 'root',
                'db.pass' => 'rootroot',
                'email' => 'root@db.org',
            ]],
            ['*root*', [
                'db.user' => 'root',
                'db.pass' => 'rootroot',
                'email' => 'root@db.org',
                'someString' => 'Connecting as root is evil ! Mike12',
                'list' => [100, 'arootb', true, 'goo'],
            ]],
            ['*root', [
                'db.user' => 'root',
                'db.pass' => 'rootroot',
            ]],
            ['null', [
                'nullValue' => null,
            ]],
            [null, [
                'nullValue' => null,
            ]],
            ['*null*', [
                'otherString' => 'null',
            ]],
            ['10', [
                'tenNumber' => 10,
            ]],
            ['10*', [
                'tenNumber' => 10,
                'numberAsString' => '10',
                'list' => [100, 'arootb', true, 'goo'],
            ]],
            ['0', [
                'number' => 0,
            ]],
            ['*0*', [
                'db.host' => '192.160.13.12',
                'number' => 0,
                'tenNumber' => 10,
                'numberAsString' => '10',
                'list' => [100, 'arootb', true, 'goo'],
            ]],
            ['true', [
                'display_errors' => true,
                'list' => [100, 'arootb', true, 'goo'],
            ]],
            ['true*', [
                'trueString' => 'true',
            ]],
            ['*.o*', [
                'email' => 'root@db.org',
            ]],
            ['*o*', [
                'db.user' => 'root',
                'db.pass' => 'rootroot',
                'email' => 'root@db.org',
                'someString' => 'Connecting as root is evil ! Mike12',
                'list' => [100, 'arootb', true, 'goo'], // once
            ]],
            ['192.160.13.12', [
                'db.host' => '192.160.13.12',
            ]],
            ['root@db.org', [
                'email' => 'root@db.org',
            ]],
            ['*@db.org', [
                'email' => 'root@db.org',
            ]],
            ['db.*', [
            ]],
            ['b**te', [
                'stringWithStar' => 'b*te',
            ]],
            ['b**t*', [
                'stringWithStar' => 'b*te',
            ]],
            ['***te', [
            ]],
            ['b***', [
                'stringWithStar' => 'b*te',
            ]],
            ['****', [
            ]],
        ];
    }
}
