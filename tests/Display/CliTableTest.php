<?php

declare(strict_types = 1);

namespace Karma\Display;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#Group['Unix']
class CliTableTest extends TestCase
{
    #[DataProvider('providerTestRender')]
    public function testRender(array $headers, array $input, string $expected): void
    {
        $table = new CliTable($input);
        $table->setHeaders($headers);

        self::assertSame($expected, $table->render());
    }

    public static function providerTestRender(): array
    {
        return [
            [['Variable', 'Dev', 'Prod'], [
                ['x', 153, 15.24],
                ['db.password', null, 'azertyroot1234#'],
                ['toto', 'some_value', false],
            ], <<<RESULT
|--------------------------------------------|
| Variable    | Dev        | Prod            |
|--------------------------------------------|
| x           | 153        | 15.24           |
| db.password | NULL       | azertyroot1234# |
| toto        | some_value | false           |
|--------------------------------------------|
RESULT
            ],
            [['Variable', 'Dev', 'Production'], [
                ['x', 153, 15.24],
                ['db.password', null, 'root'],
                ['toto', '0', true],
            ], <<<RESULT
|---------------------------------|
| Variable    | Dev  | Production |
|---------------------------------|
| x           | 153  | 15.24      |
| db.password | NULL | root       |
| toto        | 0    | true       |
|---------------------------------|
RESULT
            ],
        ];
    }

    public function testValueRenderingFunction(): void
    {
        $table = new CliTable([
            ['a', 'a1', 'a2'],
            ['B', 'B1', 'B2'],
            ['cccc', 'cccc1', 'cccc2'],
        ]);

        $table->setHeaders(['', 'e1', 'e2'])
              ->setValueRenderingFunction(static function ($value){
            return strtoupper($value);
        });

        $expected = <<<RESULT
|----------------------|
|      | E1    | E2    |
|----------------------|
| A    | A1    | A2    |
| B    | B1    | B2    |
| CCCC | CCCC1 | CCCC2 |
|----------------------|
RESULT;

        self::assertSame($expected, $table->render());
    }

    public function testWeirdCharacter(): void
    {
        $table = new CliTable([
            ['a', 'a1', 'a2'],
            ['B', 'B1', 'B2'],
            ['x<y2', 'cccc1', 'cccc2'],
        ]);

        $table->setHeaders(['', 'e1', 'e2'])
              ->enableFormattingTags();

        $expected = <<<RESULT
|----------------------|
|      | e1    | e2    |
|----------------------|
| a    | a1    | a2    |
| B    | B1    | B2    |
| x<y2 | cccc1 | cccc2 |
|----------------------|
RESULT;

        self::assertSame($expected, $table->render());
    }

    public function testEnableFormattingTags(): void
    {
        $table = new CliTable([
            ['a', 'a1', 'a2'],
            ['B', 'B1', 'B2'],
            ['<color=blue>c</color>', 'cccc1', 'cccc2'],
        ]);

        $table->setHeaders(['1234', 'e1', 'e2'])
              ->enableFormattingTags();

        // Expects that tags have no impact on column size computation
        $expected = <<<RESULT
|----------------------|
| 1234 | e1    | e2    |
|----------------------|
| a    | a1    | a2    |
| B    | B1    | B2    |
| <color=blue>c</color>    | cccc1 | cccc2 |
|----------------------|
RESULT;

        self::assertSame($expected, $table->render());
    }

    #[DataProvider('providerTestSanityChecks')]
    public function testSanityChecks(array $values): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $table = new CliTable($values);
        $table->render();
    }

    public static function providerTestSanityChecks(): array
    {
        return [
            'one dim array' => [['a', 'b', 'c']],
            'one dim assoc array' => [['a' => 0, 'b' => 1, 'c' => 2]],

            'two dim array but inconsistent row length #1' => [[['a'], ['b', 'c']]],
            'two dim array but inconsistent row length #2' => [[['a', 'b'], ['c']]],
        ];
    }

    #[DataProvider('providerTestDisplayKeys')]
    public function testDisplayKeys(bool $enableKeys, string $expected): void
    {
        $values = [
            'key1' => ['a', 'bb'],
            'key2' => [true, 3],
            [42, 51],
            'key4' => [null, 12],
            [82, 86],
        ];

        $table = new CliTable($values);
        $table->setHeaders(['colA', 'colB']);

        $result = $table->displayKeys($enableKeys)
            ->render();

        self::assertSame($expected, $result);
    }

    public static function providerTestDisplayKeys(): array
    {
        return [
            [true, <<<RESULT
|--------------------|
|      | colA | colB |
|--------------------|
| key1 | a    | bb   |
| key2 | true | 3    |
| 0    | 42   | 51   |
| key4 | NULL | 12   |
| 1    | 82   | 86   |
|--------------------|
RESULT
            ],
            [false, <<<RESULT
|-------------|
| colA | colB |
|-------------|
| a    | bb   |
| true | 3    |
| 42   | 51   |
| NULL | 12   |
| 82   | 86   |
|-------------|
RESULT
            ],
        ];
    }
}
