<?php

declare(strict_types = 1);

namespace Karma\Generator\NameTranslators;

use Karma\Generator\NameTranslator;

class NullTranslator implements NameTranslator
{
    public function translate($file, $variable)
    {
        return $variable;
    }
}
