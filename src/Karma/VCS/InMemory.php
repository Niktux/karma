<?php

namespace Karma\VCS;

class InMemory implements Vcs
{
    private
        $alreadyTrackedPattern,
        $alreadyIgnoredPattern,
        $untrackedFiles,
        $ignoredFiles;
    
    public function __construct($alreadyTrackedPattern, $alreadyIgnoredPattern)
    {
        $this->alreadyTrackedPattern = $alreadyTrackedPattern;
        $this->alreadyIgnoredPattern = $alreadyIgnoredPattern;
        
        $this->untrackedFiles = array();
        $this->ignoredFiles = array();
    }
    
    public function isTracked($filepath)
    {
        if(in_array($filepath, $this->untrackedFiles))
        {
            return false;
        }    
        
        return (bool) preg_match($this->alreadyTrackedPattern, $filepath);
    }
    
    public function untrackFile($filepath)
    {
        $this->untrackedFiles[] = $filepath;
    }
    
    public function isIgnored($filepath)
    {
        if(in_array($filepath, $this->ignoredFiles))
        {
            return true;
        }    
        
        return (bool) preg_match($this->alreadyIgnoredPattern, $filepath);
    }
    
    public function ignoreFile($filepath)
    {
        $this->ignoredFiles[] = $filepath;    
    }
    
    public function getUntrackedFiles()
    {
        return $this->untrackedFiles;
    }
    
    public function getIgnoredFiles()
    {
        return $this->ignoredFiles;
    }
}