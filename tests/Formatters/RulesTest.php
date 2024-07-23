<?php

declare(strict_types = 1);

namespace Karma\Formatters;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RulesTest extends TestCase
{
    private Rules
        $formatter;

    protected function setUp(): void
    {
        $rules = [
            ' <true>' => 'string true',
            '<false> ' => 'string false',
            '<null>' => 0,
            'foobar' => 'barfoo',
            'footrue' => true,
            ' <string> ' => '"<string>"',
        ];

        $this->formatter = new Rules($rules);
    }

    #[DataProvider('providerTestFormat')]
    public function testFormat($input, $expected): void
    {
        $result = $this->formatter->format($input);
        self::assertSame($expected, $result);
    }

    public static function providerTestFormat(): array
    {
        return [
            'boolean true' => [true, 'string true'],
            'string true' => ['true', '"true"'],
            'other string true' => ['<true>', '"<true>"'],
            'footrue' => ['footrue', true],

            'boolean false' => [false, 'string false'],
            'string false' => ['false', '"false"'],
            'other string false' => ['<false>', '"<false>"'],

            'null' => [null, 0],
            'string null' => ['null', '"null"'],
            'other string null' => ['<null>', '"<null>"'],

            'zero' => [0, 0],
            'string zero' => ['0', '"0"'],
            'other string zero' => ['<0>', '"<0>"'],

            'foo' => ['foo', '"foo"'],
            'foobar' => ['foobar', 'barfoo'],
            'barfoobarfoo' => ['barfoobarfoo', '"barfoobarfoo"'],
        ];
    }
}
