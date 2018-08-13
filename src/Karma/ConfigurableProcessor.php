<?php

declare(strict_types = 1);

namespace Karma;

interface ConfigurableProcessor
{
    public function setDryRun($value = true);
    public function enableBackup($value = true);
    public function setSystemEnvironment(string $environment): ConfigurableProcessor;
}
