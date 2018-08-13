<?php

declare(strict_types = 1);

namespace Karma\Configuration\Parser;

class NullParser extends AbstractSectionParser
{
    protected function parseLine($line)
    {
        throw new \RuntimeException('File must start with a group name');
    }
}
