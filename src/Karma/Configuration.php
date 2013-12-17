<?php

namespace Karma;

interface Configuration
{
    public function read($variable, $environment);
}