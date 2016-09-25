<?php

namespace Karma\Configuration\Parser;

abstract class AbstractSectionParser implements SectionParser
{
    const
        COMMENT_CHARACTER = '#';

    protected
        $currentFilePath,
        $currentLineNumber;

    public function __construct()
    {
        $this->currentFilePath = null;
        $this->currentLineNumber = null;
    }

    public function setCurrentFile($filePath)
    {
        $this->currentFilePath = $filePath;
    }

    protected function isACommentLine($line)
    {
        return strpos(trim($line), self::COMMENT_CHARACTER) === 0;
    }

    final public function parse($line, $lineNumber)
    {
        $this->currentLineNumber = $lineNumber;

        return $this->parseLine($line);
    }

    abstract protected function parseLine($line);

    protected function triggerError($message, $title = 'Syntax error')
    {
        throw new \RuntimeException(sprintf(
            '%s in %s line %d : %s',
            $title,
            $this->currentFilePath,
            $this->currentLineNumber,
            $message
        ));
    }

    public function postParse()
    {
    }
}
