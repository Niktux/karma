<?php

declare(strict_types = 1);

namespace Karma\FormatterProviders;

use Karma\Formatter;
use Karma\FormatterProvider;
use Karma\Formatters\Raw;

class NullProvider implements FormatterProvider
{
    private Raw
        $raw;

    public function __construct()
    {
        $this->raw = new Raw();
    }

    public function hasFormatter(?string $index): bool
    {
        return false;
    }

    public function getFormatter(?string $fileExtension, ?string $index = null): Formatter
    {
        return $this->raw;
    }
}
