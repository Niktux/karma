<?php

declare(strict_types = 1);

namespace Karma;

interface FormatterProvider
{
    public function hasFormatter(?string $index): bool;

    public function formatter(?string $fileExtension, ?string $index = null): Formatter;
}
