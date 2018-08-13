<?php

declare(strict_types = 1);

namespace Karma;

class Console
{
    private
        $app;

    public function __construct(Application $dic)
    {
        $this->app = new \Symfony\Component\Console\Application('Karma', Application::VERSION);

        $this->app->add(new Command\Hydrate($dic));
        $this->app->add(new Command\Generate($dic));
        $this->app->add(new Command\Display($dic));
        $this->app->add(new Command\Diff($dic));
        $this->app->add(new Command\Rollback($dic));
    }

    public function run()
    {
        $this->app->run();
    }

    public function getConsoleApplication()
    {
        return $this->app;
    }
}
