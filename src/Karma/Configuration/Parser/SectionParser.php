<?php

declare(strict_types = 1);

namespace Karma\Configuration\Parser;

interface SectionParser
{
    public function parse(string $line, int $lineNumber): void;
    public function setCurrentFile(string $filePath): void;
    public function postParse(): void;
}
