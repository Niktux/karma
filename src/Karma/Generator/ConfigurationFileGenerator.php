<?php

namespace Karma\Generator;

use Karma\ConfigurableProcessor;

interface ConfigurationFileGenerator extends ConfigurableProcessor
{
    const
        DELIMITER = '.';

    public function generate($environment);
}
