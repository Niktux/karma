<?php

namespace Karma\Configuration\Parser;

class IncludeParser extends AbstractSectionParser
{
    private
        $files;
    
    public function __construct()
    {
        $this->files = array();
    }
    
    public function parse($line, $lineNumber)
    {
        if($this->isACommentLine($line))
        {
            return true;
        }
                
        $this->checkFilenameIsValid($line, $lineNumber);
        
        $this->files[] = $line;
    }
    
    private function checkFilenameIsValid($filename, $lineNumber)
    {
        if(! preg_match('~.*\.conf$~', $filename))
        {
            throw new \RuntimeException(sprintf(
                'Invalid dependency in %s line %d : %s is not a valid file name',
                $this->currentFilePath,
                $lineNumber,
                $filename
            ));    
        }
    }
    
    public function getCollectedFiles()
    {
        return $this->files;
    }
}