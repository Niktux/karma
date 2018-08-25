<?php

declare(strict_types = 1);

namespace Karma\Configuration\Parser;

abstract class AbstractSectionParser implements SectionParser
{
    private const
        COMMENT_CHARACTER = '#';

    protected
        $currentFilePath,
        $currentLineNumber;

    public function __construct()
    {
        $this->currentFilePath = null;
        $this->currentLineNumber = null;
    }

    public function setCurrentFile(string $filePath): void
    {
        $this->currentFilePath = $filePath;
    }

    protected function isACommentLine(string $line): bool
    {
        return strpos(trim($line), self::COMMENT_CHARACTER) === 0;
    }

    final public function parse(string $line, int $lineNumber): void
    {
        $this->currentLineNumber = $lineNumber;

        $this->parseLine($line);
    }

    abstract protected function parseLine(string $line): void;

    protected function triggerError(string $message, string $title = 'Syntax error'): void
    {
        throw new \RuntimeException(sprintf(
            '%s in %s line %d : %s',
            $title,
            $this->currentFilePath,
            $this->currentLineNumber,
            $message
        ));
    }

    public function postParse(): void
    {
    }
}
