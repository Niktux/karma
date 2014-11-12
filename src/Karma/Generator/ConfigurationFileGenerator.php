<?php

namespace Karma\Generator;

interface ConfigurationFileGenerator
{
    const
        DELIMITER = '.';

    public function generate($environment);
    public function setDryRun($value = true);
}