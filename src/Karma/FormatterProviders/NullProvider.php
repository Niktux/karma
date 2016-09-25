<?php

namespace Karma\FormatterProviders;

use Karma\FormatterProvider;
use Karma\Formatters\Raw;

class NullProvider implements FormatterProvider
{
    private
        $raw;

    public function __construct()
    {
        $this->raw = new Raw();
    }

    public function hasFormatter($index)
    {
        return false;
    }

    public function getFormatter($fileExtension, $index = null)
    {
        return $this->raw;
    }
}
