<?php

declare(strict_types = 1);

namespace Karma;

interface Formatter
{
    public function format(mixed $value): mixed;
}
