<?php

declare(strict_types = 1);

namespace Karma\Generator;

use Karma\ConfigurableProcessor;

interface ConfigurationFileGenerator extends ConfigurableProcessor
{
    const
        DELIMITER = '.';

    public function generate(string $environment): void;
}
