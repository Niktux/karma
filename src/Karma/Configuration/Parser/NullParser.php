<?php

namespace Karma\Configuration\Parser;

class NullParser extends AbstractGroupParser
{
    public function parse($line)
    {
        throw new \RuntimeException('File must start with a group name');
    }
}