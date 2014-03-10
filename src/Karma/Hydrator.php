<?php

namespace Karma;

use Gaufrette\Filesystem;
use Psr\Log\NullLogger;

class Hydrator
{
    use \Karma\Logging\LoggerAware;

    const
        VARIABLE_REGEX = '~<%(?P<variableName>[A-Za-z0-9_\.]+)%>~';
    
    private
        $sources,
        $suffix,
        $reader,
        $dryRun,
        $enableBackup,
        $finder;
    
    public function __construct(Filesystem $sources, Configuration $reader, Finder $finder)
    {
        $this->logger = new NullLogger();
        
        $this->sources = $sources;
        $this->reader = $reader;
        $this->finder = $finder;
        
        $this->suffix = Application::DEFAULT_DISTFILE_SUFFIX;
        $this->dryRun = false;
        $this->enableBackup = false;
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
        return $this->finder->findFiles("~$this->suffix$~");
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
        $targetContent = preg_replace_callback(self::VARIABLE_REGEX, function(array $matches) use($environment){
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
                $backupFile = $targetFile . Application::BACKUP_SUFFIX;
                $this->sources->write($backupFile, $this->sources->read($targetFile), true);
            }
        }
    }
    
    public function rollback()
    {
        $distFiles = $this->collectDistFiles();
    
        foreach($distFiles as $file)
        {
            $this->rollbackFile($file);
        }
    }
    
    private function rollbackFile($file)
    {
        $this->debug("- $file");
    
        $targetFile = substr($file, 0, strlen($this->suffix) * -1);
        $backupFile = $targetFile . Application::BACKUP_SUFFIX;
    
        if($this->sources->has($backupFile))
        {
            $this->info("  Writing $targetFile");
    
            if($this->dryRun === false)
            {
                $backupContent = $this->sources->read($backupFile);
                $this->sources->write($targetFile, $backupContent, true);
            }
        }
    }
}