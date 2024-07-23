<?php

declare(strict_types = 1);

namespace Karma;

interface Configuration
{
    public const string
        SYSTEM_VARIABLE_FLAG = '@',
        NOT_FOUND = 'karma-notfound';

    public function read(string $variable, ?string $environment = null);

    public function setDefaultEnvironment(string $environment): void;

    public function allVariables(): array;
    public function allValuesForEnvironment(?string $environment = null);

    public function overrideVariable(string $variable, $value): void;

    public function setCustomData(string $customDataName, $value): void;

    public function isSystem(string $variableName): bool;
}
