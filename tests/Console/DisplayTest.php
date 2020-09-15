<?php

declare(strict_types = 1);

namespace Karma\Console;

require_once __DIR__ . '/CommandTestCase.php';

class DisplayTest extends CommandTestCase
{
    /**
     * @dataProvider providerTestDisplay
     */
    public function testDisplay(string $env): void
    {
        $this->runCommand('display', array('--env' => $env));

        $reader = $this->app['configuration'];
        $valueFoo = $reader->read('app.foo', $env);

        $this->assertDisplay("~Display $env values~");
        $this->assertDisplay("~$valueFoo~");
    }

    public function providerTestDisplay(): array
    {
        return [
            ['dev'],
            ['prod'],
        ];
    }

    public function testValueFilter(): void
    {
        $env = 'dev';

        $reader = $this->app['configuration'];
        $valueFoo = $reader->read('app.foo', $env);
        $valueBar = $reader->read('app.bar', $env);

        $this->runCommand('display', array('--value' => $valueBar));

        // Unit Test sanity test
        self::assertNotSame($valueFoo, $valueBar);

        $this->assertDisplay("~Display $env values~");
        $this->assertNotDisplay("~$valueFoo~");
        $this->assertNotDisplay("~$valueFoo~");
    }
}
