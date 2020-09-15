<?php

declare(strict_types = 1);

namespace Karma\Generator;

use Karma\ConfigurableProcessor;

interface ConfigurationFileGenerator extends ConfigurableProcessor
{
    public const
        DELIMITER = '.';

    public function generate(string $environment): void;
}
