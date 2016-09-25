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

    protected function parseLine($line)
    {
        if($this->isACommentLine($line))
        {
            return true;
        }

        $this->checkFilenameIsValid($line);

        $this->files[] = $line;
    }

    private function checkFilenameIsValid($filename)
    {
        if(! preg_match('~.*\.conf$~', $filename))
        {
            $this->triggerError("$filename is not a valid file name", 'Invalid dependency');
        }
    }

    public function getCollectedFiles()
    {
        return $this->files;
    }
}
