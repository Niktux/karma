<?php

declare(strict_types = 1);

namespace Karma\Configuration\Parser;

interface SectionParser
{
    public function parse($line, $lineNumber);
    public function setCurrentFile($filePath);
    public function postParse();
}
