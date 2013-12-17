<?php

namespace Karma;

use Symfony\Component\Console\Application;

class Console
{
    public function run()
    {
        $app = new Application();
        $app->add(new Command\Hydrate());
        $app->run();
    }
}