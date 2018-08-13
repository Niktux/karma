<?php

declare(strict_types = 1);

namespace Karma\Command;

require_once __DIR__ . '/CommandTestCase.php';

class DiffTest extends CommandTestCase
{
    public function testDiff()
    {
        $this->runCommand('diff', array(
            'env1' => 'dev',
            'env2' => 'prod'
        ));

        $reader = $this->app['configuration'];
        $valueBar = $reader->read('app.bar');

        // Command must sumup displayed environments
        $this->assertDisplay("~dev~");
        $this->assertDisplay("~prod~");

        // A CLI table is displayed
        $this->assertDisplay("~|---~");

        // Bar has only a default clause => no differences between environments
        $this->assertNotDisplay("~$valueBar~");
    }
}
