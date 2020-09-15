<?php

declare(strict_types = 1);

namespace Karma\Console;

require_once __DIR__ . '/CommandTestCase.php';

class DiffTest extends CommandTestCase
{
    public function testDiff(): void
    {
        $this->runCommand('diff', [
            'env1' => 'dev',
            'env2' => 'prod'
        ]);

        $reader = $this->app['configuration'];
        $valueBar = $reader->read('app.bar');

        // Command must sum up displayed environments
        $this->assertDisplay("~dev~");
        $this->assertDisplay("~prod~");

        // A CLI table is displayed
        $this->assertDisplay("~|---~");

        // Bar has only a default clause => no differences between environments
        $this->assertNotDisplay("~$valueBar~");
    }
}
