<?php

declare(strict_types = 1);

namespace Karma\Generator\NameTranslators;

use Karma\Generator\NameTranslator;

final readonly class NullTranslator implements NameTranslator
{
    public function translate(string $file, string $variable): string
    {
        return $variable;
    }
}
