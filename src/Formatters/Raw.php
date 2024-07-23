<?php

declare(strict_types = 1);

namespace Karma\Formatters;

use Karma\Formatter;

final readonly class Raw implements Formatter
{
    public function format(mixed $value): mixed
    {
        return $value;
    }    
}
