<?php

namespace Karma\Generator;

interface ConfigurationFileGenerator
{
    const
        DELIMITER = '.';

    public function generate($environment);
}