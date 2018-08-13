<?php

declare(strict_types = 1);

namespace Karma\Command;

use Karma\Application;
use Gaufrette\Adapter\InMemory;
use Karma\Console;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

abstract class CommandTestCase extends TestCase
{
    protected
        $app,
        $commandTester;

    protected function setUp()
    {
        $masterContent = <<< CONFFILE
[variables]
app.foo:
    dev = value1
    default = value2
app.bar:
    default = valueAll
CONFFILE;

        $this->app = new Application();
        $this->app['configuration.fileSystem.adapter'] = new InMemory(array(
            Application::DEFAULT_MASTER_FILE => $masterContent,
        ));

        $this->app['profile.fileSystem.adapter'] = function($c) {
            return new InMemory();
        };
    }

    protected function runCommand($commandName, array $commandArguments = array())
    {
        $console = new Console($this->app);
        $command = $console
            ->getConsoleApplication()
            ->find($commandName);

        $this->commandTester = new CommandTester($command);

        $commandArguments = array_merge(
            array('command' => $command->getName()),
            $commandArguments
        );

        $this->commandTester->execute($commandArguments);
    }

    protected function assertDisplay($regex)
    {
        $this->assertRegExp($regex, $this->commandTester->getDisplay());
    }

    protected function assertNotDisplay($regex)
    {
        $this->assertNotRegExp($regex, $this->commandTester->getDisplay());
    }
}
