<?php

declare(strict_types = 1);

namespace Karma\Formatters;

use Karma\Formatter;

class Raw implements Formatter
{
    public function format($value)
    {
        return $value;
    }    
}
