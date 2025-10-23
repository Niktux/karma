<?php

declare(strict_types = 1);

namespace Karma\Console;

use Karma\Application;
use Karma\Filesystem\Adapters\Memory;
use Karma\Console;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

abstract class CommandTestCase extends TestCase
{
    protected Application
        $app;
    protected CommandTester
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
        $this->app['configuration.fileSystem.adapter'] = new Memory([
            Application::DEFAULT_MASTER_FILE => $masterContent,
        ]);

        $this->app['profile.fileSystem.adapter'] = static function() {
            return new Memory();
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
