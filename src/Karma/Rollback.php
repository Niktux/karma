<?php

namespace Karma;

use Gaufrette\Filesystem;
use Psr\Log\NullLogger;

class Rollback
{
    use \Karma\Logging\LoggerAware;
    
    private
        $sources,
        $suffix,
        $dryRun; 
    
    public function __construct(Filesystem $sources)
    {
        $this->logger = new NullLogger();
        $this->sources = $sources;
        $this->suffix = Application::DEFAULT_DISTFILE_SUFFIX;
        $this->dryRun = false;
    }
    
    public function setSuffix($suffix)
    {
        $this->suffix = $suffix;
        
        return $this;
    }
    
    public function setDryRun($value = true)
    {
        $this->dryRun = (bool) $value;
    
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
        $this->debug("- $file");
        
        $targetFile = substr($file, 0, strlen($this->suffix) * -1);
        $backupFile = $targetFile . Application::BACKUP_SUFFIX;
        
        if($this->sources->has($backupFile))
        {
            $this->info("Writing $targetFile");
            
            $backupContent = $this->sources->read($backupFile);
            $this->sources->write($targetFile, $backupContent, true);
        }
    }
}