<?php

namespace Karma;

interface ConfigurableProcessor
{
    public function setDryRun($value = true);
    public function enableBackup($value = true);
    public function setSystemEnvironment($environment);
}
