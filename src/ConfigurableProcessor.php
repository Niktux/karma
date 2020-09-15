<?php

declare(strict_types = 1);

namespace Karma;

interface ConfigurableProcessor
{
    public function setDryRun(bool $value = true): ConfigurableProcessor;
    public function enableBackup(bool $value = true): ConfigurableProcessor;
    public function setSystemEnvironment(?string $environment): ConfigurableProcessor;
}
