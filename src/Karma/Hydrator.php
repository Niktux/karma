<?php

namespace Karma;

use Gaufrette\Filesystem;
use Psr\Log\NullLogger;

class Hydrator
{
    use \Karma\Logging\LoggerAware;
    
    const
        BACKUP_SUFFIX = '~';
    
    private
        $sources,
        $suffix,
        $reader,
        $dryRun,
        $enableBackup;
    
    public function __construct(Filesystem $sources, $suffix, Configuration $reader)
    {
        $this->logger = new NullLogger();
        
        $this->sources = $sources;
        $this->suffix = $suffix;
        $this->reader = $reader;
        
        $this->dryRun = false;
        $this->enableBackup = false;
    }
    
    public function setDryRun($value = true)
    {
        $this->dryRun = (bool) $value;
        
        return $this;
    }
    
    public function enableBackup($value = true)
    {
        $this->enableBackup = (bool) $value;
        
        return $this;
    }
    
    public function hydrate($environment)
    {
        $distFiles = $this->collectDistFiles();
        
        foreach($distFiles as $file)
        {
            $this->hydrateFile($file, $environment);
        }
        
        $this->info(sprintf(
           '%d files generated',
            count($distFiles)
        ));
    }
    
    private function collectDistFiles()
    {
        $finder = new Finder($this->sources);
        
        return $finder->findFiles($this->suffix);
    }
    
    private function hydrateFile($file, $environment)
    {
        $content = $this->sources->read($file);
        $targetContent = $this->injectValues($file, $content, $environment);
        
        $targetFile = substr($file, 0, strlen($this->suffix) * -1);
        $this->debug("Write $targetFile");

        if($this->dryRun === false)
        {
            $this->backupFile($targetFile);
            $this->sources->write($targetFile, $targetContent, true);
        }
    }
    
    private function injectValues($sourceFile, $content, $environment)
    {
        $targetContent = preg_replace_callback('~<%(?P<variableName>[A-Za-z0-9_\.]+)%>~', function(array $matches) use($environment){
            return $this->reader->read($matches['variableName'], $environment);
        }, $content, -1, $count);
        
        if($count === 0)
        {
            $this->warning("No variable found in $sourceFile");
        }
        
        return $targetContent;
    }
    
    private function backupFile($targetFile)
    {
        if($this->enableBackup === true)
        {
            if($this->sources->has($targetFile))
            {
                $backupFile = $targetFile . self::BACKUP_SUFFIX;
                $this->sources->write($backupFile, $this->sources->read($targetFile), true);
            }
        }
    }
}