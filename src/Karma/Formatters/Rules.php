<?php

namespace Karma\Formatters;

use Karma\Formatter;

class Rules implements Formatter
{
    private
        $rules;
    
    public function __construct(array $rules)
    {
        $this->rules = $rules;
    }
    
    public function format($value)
    {
        // TODO
        return $value;
    }
}
