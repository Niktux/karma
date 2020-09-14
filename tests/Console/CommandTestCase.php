<?php

declare(strict_types = 1);

namespace Karma\Console;

use Karma\Application;
use Gaufrette\Adapter\InMemory;
use Karma\Console;
use PHPUnit\Framework\TestCase;
use Pimple\Container;
use Symfony\Component\Console\Tester\CommandTester;

abstract class CommandTestCase extends TestCase
{
    protected
        $app,
        $commandTester;

    protected function setUp(): void
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
        $this->app['configuration.fileSystem.adapter'] = new InMemory([
            Application::DEFAULT_MASTER_FILE => $masterContent,
        ]);

        $this->app['profile.fileSystem.adapter'] = function(Container $c) {
            return new InMemory();
        };
    }

    protected function runCommand(string $commandName, array $commandArguments = []): void
    {
        $console = new Console($this->app);
        $command = $console
            ->getConsoleApplication()
            ->find($commandName);

        $this->commandTester = new CommandTester($command);

        $commandArguments = array_merge(
            ['command' => $command->getName()],
            $commandArguments
        );

        $this->commandTester->execute($commandArguments);
    }

    protected function assertDisplay(string $regex): void
    {
        self::assertMatchesRegularExpression($regex, $this->commandTester->getDisplay());
    }

    protected function assertNotDisplay(string $regex): void
    {
        self::assertDoesNotMatchRegularExpression($regex, $this->commandTester->getDisplay());
    }
}
