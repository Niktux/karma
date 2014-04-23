<?php

namespace Karma\Formatters;

use Karma\Formatter;

class Raw implements Formatter
{
    public function format($value)
    {
        return $value;
    }    
}
