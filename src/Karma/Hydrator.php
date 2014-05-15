<?php

namespace Karma;

use Gaufrette\Filesystem;
use Psr\Log\NullLogger;
use Karma\FormatterProviders\NullProvider;

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
        $finder,
        $formatterProvider;
    
    public function __construct(Filesystem $sources, Configuration $reader, Finder $finder, FormatterProvider $formatterProvider = null)
    {
        $this->logger = new NullLogger();
        
        $this->sources = $sources;
        $this->reader = $reader;
        $this->finder = $finder;
        
        $this->suffix = Application::DEFAULT_DISTFILE_SUFFIX;
        $this->dryRun = false;
        $this->enableBackup = false;
        
        $this->formatterProvider = $formatterProvider;
        if($this->formatterProvider === null)
        {
            $this->formatterProvider = new NullProvider();
        }
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
    
    public function setFormatterProvider(FormatterProvider $formatterProvider)
    {
        $this->formatterProvider = $formatterProvider;
        
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
        $replacementCounter = 0;
        
        $replacementCounter += $this->injectScalarValues($content, $environment);
        $replacementCounter += $this->injectListValues($content, $environment);
        
        if($replacementCounter === 0)
        {
            $this->warning("No variable found in $sourceFile");
        }
        
        return $content;
    }
    
    private function injectScalarValues(& $content, $environment)
    {
        $formatter = $this->formatterProvider->getFormatter();
        
        $content = preg_replace_callback(self::VARIABLE_REGEX, function(array $matches) use($environment, $formatter)
        {
            $value = $this->reader->read($matches['variableName'], $environment);
        
            if(is_array($value))
            {
                // don't replace lists at this time
                return $matches[0];
            }
        
            return $formatter->format($value);
        
        }, $content, -1, $count);

        return $count;
    }
    
    private function injectListValues(& $content, $environment)
    {
        $formatter = $this->formatterProvider->getFormatter();
        $replacementCounter = 0;
        
        $eol = $this->detectEol($content);
        
        while(preg_match(self::VARIABLE_REGEX, $content))
        {
            $lines = explode($eol, $content);
            $result = array();
            
            foreach($lines as $line)
            {
                if(preg_match(self::VARIABLE_REGEX, $line, $matches))
                {
                    $values = $this->reader->read($matches['variableName'], $environment);
                    
                    if(is_array($values))
                    {
                        $replacementCounter++; 
                        foreach($values as $value)
                        {
                            $result[] = preg_replace(self::VARIABLE_REGEX, $value, $line, 1);
                        }

                        continue;
                    }
                }

                $result[] = $line;
            }
            
            $content = implode($eol, $result); 
        }
        
        return $replacementCounter; 
    }
    
    private function detectEol($content)
    {
        $types = array("\r\n", "\r", "\n");
        
        foreach($types as $type)
        {
            if(strpos($content, $type) !== false)
            {
                return $type;
            }
        }
        
        return "\n";
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