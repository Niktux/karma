<?php

declare(strict_types = 1);

namespace Karma\Configuration\Parser;

final class NullParser extends AbstractSectionParser
{
    protected function parseLine(string $line): void
    {
        throw new \RuntimeException('File must start with a section name');
    }
}
