<?php

declare(strict_types = 1);

namespace Karma;

class Console
{
    private \Symfony\Component\Console\Application
        $app;

    public function __construct(Application $dic)
    {
        $this->app = new \Symfony\Component\Console\Application('Karma', Application::VERSION);

        $this->app->add(new Console\Hydrate($dic));
        $this->app->add(new Console\Generate($dic));
        $this->app->add(new Console\Display($dic));
        $this->app->add(new Console\Diff($dic));
        $this->app->add(new Console\Rollback($dic));
    }

    public function run(): void
    {
        $this->app->run();
    }

    public function getConsoleApplication(): \Symfony\Component\Console\Application
    {
        return $this->app;
    }
}
