<?php

use Karma\Application;
use Gaufrette\Adapter\InMemory;
use Karma\Console;
use Symfony\Component\Console\Tester\CommandTester;

abstract class CommandTestCase extends PHPUnit_Framework_TestCase
{
    protected
        $app,
        $commandTester;

    protected function setUp()
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
        $this->app['configuration.fileSystem.adapter'] = new InMemory(array(
            Application::DEFAULT_MASTER_FILE => $masterContent,
        ));
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