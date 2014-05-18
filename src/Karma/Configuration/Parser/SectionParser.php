<?php

namespace Karma\Configuration\Parser;

interface SectionParser
{
    public function parse($line, $lineNumber);
    public function setCurrentFile($filePath);
    public function postParse();
}