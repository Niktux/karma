<?php

declare(strict_types = 1);

namespace Karma\Console;

require_once __DIR__ . '/CommandTestCase.php';

use Karma\Application;
use Gaufrette\Adapter\InMemory;

class ProfileTest extends CommandTestCase
{
    protected function setUp(): void
    {
        $masterContent = <<< CONFFILE
[variables]
foo:
    dev = value1
    default = value2
bar:
    default = valueAll
CONFFILE;

        $this->app = new Application();
        $this->app['configuration.fileSystem.adapter'] = new InMemory([
            'masterAlias.conf' => $masterContent,
        ]);

        $profileContent = <<< YAML
master: masterAlias.conf
confDir: env
suffix: -tpl
YAML;

        $this->app['profile.fileSystem.adapter'] = new InMemory([
        	Application::PROFILE_FILENAME => $profileContent
        ]);
    }

    public function testDisplay(): void
    {
        $env = 'dev';
        $this->runCommand('display', ['--env' => $env]);

        $reader = $this->app['configuration'];
        $valueFoo = $reader->read('foo', $env);

        $this->assertDisplay("~Display $env values~");
        $this->assertDisplay("~$valueFoo~");
    }
}
