<?php

namespace Karma\Configuration\Parser;

interface SectionParser
{
    public function parse($line);
    public function setCurrentFile($filePath);
}