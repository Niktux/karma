<?php

declare(strict_types = 1);

namespace Karma\Generator;

interface NameTranslator
{
    public function translate(string $file, string $variable): string;
}
