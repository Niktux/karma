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

        $this->app->add(new Console\Hydrate($dic));
        $this->app->add(new Console\Generate($dic));
        $this->app->add(new Console\Display($dic));
        $this->app->add(new Console\Diff($dic));
        $this->app->add(new Console\Rollback($dic));
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
