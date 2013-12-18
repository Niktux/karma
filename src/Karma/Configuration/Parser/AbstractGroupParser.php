<?php

namespace Karma\Configuration\Parser;

abstract class AbstractGroupParser implements GroupParser
{
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
}