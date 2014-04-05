<?php

namespace Karma\Configuration\Parser;

abstract class AbstractGroupParser implements GroupParser
{
    const
        COMMENT_CHARACTER = '#';
    
    protected
        $currentFilePath;
    
    public function __construct()
    {
        $this->currentFilePath = null;
    }
    
    public function setCurrentFile($filePath)
    {
        $this->currentFilePath = $filePath;
    }
    
    protected function isACommentLine($line)
    {
        return strpos(trim($line), self::COMMENT_CHARACTER) === 0;
    }
}