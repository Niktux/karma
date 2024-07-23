<?php

declare(strict_types = 1);

namespace Karma\Configuration\Parser;

final class IncludeParser extends AbstractSectionParser
{
    private array
        $files;

    public function __construct()
    {
        parent::__construct();

        $this->files = [];
    }

    protected function parseLine(string $line): void
    {
        $this->checkFilenameIsValid($line);

        $this->files[] = $line;
    }

    private function checkFilenameIsValid(string $filename): void
    {
        if(! preg_match('~.*\.conf$~', $filename))
        {
            $this->triggerError("$filename is not a valid file name", 'Invalid dependency');
        }
    }

    public function collectedFiles(): array
    {
        return $this->files;
    }
}
