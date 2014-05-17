<?php

namespace Karma\Configuration\Parser;

class NullParser extends AbstractSectionParser
{
    public function parse($line, $lineNumber)
    {
        throw new \RuntimeException('File must start with a group name');
    }
}