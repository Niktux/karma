<?php

namespace Karma;

use Gaufrette\Filesystem;

class Rollback
{
    private
        $sources,
        $suffix; 
    
    public function __construct(Filesystem $sources)
    {
        $this->sources = $sources;
        $this->suffix = Application::DEFAULT_DISTFILE_SUFFIX;
    }
    
    public function setSuffix($suffix)
    {
        $this->suffix = $suffix;
        
        return $this;
    }
    
    public function exec()
    {
        $distFiles = $this->collectDistFiles();

        foreach($distFiles as $file)
        {
            $this->rollback($file);
        }
    }
    
    private function collectDistFiles()
    {
        $finder = new Finder($this->sources);
    
        return $finder->findFiles($this->suffix);
    }
    
    private function rollback($file)
    {
        $targetFile = substr($file, 0, strlen($this->suffix) * -1);
        $backupFile = $targetFile . Application::BACKUP_SUFFIX;
        
        if($this->sources->has($backupFile))
        {
            $backupContent = $this->sources->read($backupFile);
            $this->sources->write($targetFile, $backupContent, true);
        }
    }
}