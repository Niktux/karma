<?php

namespace Karma\Configuration\Parser;

class IncludeParser extends AbstractGroupParser
{
    private
        $files;
    
    public function __construct()
    {
        $this->files = array();
    }
    
    public function parse($line)
    {
        $this->checkFilenameIsValid($line);
        
        $this->files[] = $line;
    }
    
    private function checkFilenameIsValid($filename)
    {
        if(! preg_match('~.*\.conf$~', $filename))
        {
            throw new \RuntimeException(sprintf(
                'Invalid dependency in %s : %s is not a valid file name',
                $this->currentFilePath,
                $filename
            ));    
        }
    }
    
    public function getCollectedFiles()
    {
        return $this->files;
    }
}