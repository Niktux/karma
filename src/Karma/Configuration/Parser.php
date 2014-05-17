<?php

namespace Karma\Configuration;

use Gaufrette\Filesystem;
use Karma\Configuration\Parser\NullParser;
use Karma\Configuration\Parser\IncludeParser;
use Karma\Configuration\Parser\VariableParser;
use Karma\Configuration\Parser\ExternalParser;
use Psr\Log\NullLogger;
use Karma\Configuration\Parser\GroupParser;

class Parser
{
    use \Karma\Logging\LoggerAware;
    
    const
        INCLUDES = 'includes',
        VARIABLES = 'variables',
        EXTERNALS = 'externals',
        GROUPS = 'groups';
    
    private
        $parsers,
        $currentParser,
        $parsedFiles,
        $fs,
        $eol;
    
    public function __construct(Filesystem $fs)
    {
        $this->logger = new NullLogger();
        
        $this->parsers = array(
            self::VARIABLES => new VariableParser(),  
        );

        $this->parsedFiles = array();
        $this->fs = $fs; 
        $this->eol = "\n";
    }
    
    public function setEOL($eol)
    {
        $this->eol = $eol;
        
        return $this;
    }
    
    public function enableIncludeSupport()
    {
        if(! isset($this->parsers[self::INCLUDES]))
        {
            $this->parsers[self::INCLUDES] = new IncludeParser();
        }
        
        return $this;
    }
    
    public function enableExternalSupport()
    {
        if(! isset($this->parsers[self::EXTERNALS]))
        {
            $this->parsers[self::EXTERNALS] = new ExternalParser(new Parser($this->fs));
        }
        
        return $this;
    }
    
    public function enableGroupSupport()
    {
        if(! isset($this->parsers[self::GROUPS]))
        {
            $this->parsers[self::GROUPS] = new GroupParser();
        }
        
        return $this;
    }
    
    public function parse($masterFilePath)
    {
        try
        {
            $this->parseFromMasterFile($masterFilePath);
            
            $variables = $this->getVariables();
            $this->printExternalFilesStatus();
            
            return $variables;
        }
        catch(\RuntimeException $e)
        {
            $this->error($e->getMessage());
            throw $e;
        }
    }
    
    private function parseFromMasterFile($masterFilePath)
    {
        $files = array($masterFilePath);
        
        while(! empty($files))
        {
            foreach($files as $file)
            {
                $this->readFile($file);
            }
            
            if(isset($this->parsers[self::INCLUDES]))
            {
                $includeParser = $this->parsers[self::INCLUDES];
                $files = $includeParser->getCollectedFiles();
            }

            // Avoid loop
            $files = array_diff($files, $this->parsedFiles);
        }
    }
    
    private function readFile($filePath)
    {
        $lines = $this->extractLines($filePath);
        $this->changeCurrentFile($filePath);
        
        if(empty($lines))
        {
            $this->warning("Empty file ($filePath)");
        }
        
        $this->currentParser = new NullParser();
        foreach($lines as $line)
        {
            $sectionName = $this->extractSectionName($line);
            if($sectionName !== null)
            {
                $this->switchSectionParser($sectionName);
                continue;
            }

            $this->currentParser->parse($line);
        }
        
        $this->parsers[self::VARIABLES]->endOfFileCheck();
    }
    
    private function extractLines($filePath)
    {
        if(! $this->fs->has($filePath))
        {
            throw new \RuntimeException("$filePath does not exist");
        }
        
        $content = $this->fs->read($filePath);

        $lines = explode($this->eol, $content);
        $lines = $this->trimLines($lines);
        $lines = $this->removeEmptyLines($lines);

        $this->parsedFiles[] = $filePath;
        
        return $lines;
    }
    
    private function trimLines(array $lines)
    {
        return array_map('trim', $lines);
    }
    
    private function removeEmptyLines(array $lines)
    {
        return array_filter($lines);
    }
    
    private function changeCurrentFile($filePath)
    {
        $this->info("Reading $filePath");
        
        foreach($this->parsers as $parser)
        {
            $parser->setCurrentFile($filePath);
        }
    }
    
    private function extractSectionName($line)
    {
        $sectionName = null;
        
        // [.*]
        if(preg_match('~^\[(?P<groupName>[^\]]+)\]$~', $line, $matches))
        {
            $sectionName = trim(strtolower($matches['groupName']));
        }
        
        return $sectionName;
    }
    
    private function switchSectionParser($sectionName)
    {
        if(! isset($this->parsers[$sectionName]))
        {
            throw new \RuntimeException('Unknown section name ' . $sectionName);
        }
        
        $this->currentParser = $this->parsers[$sectionName];
    }
    
    private function getVariables()
    {
        return $this->parsers[self::VARIABLES]->getVariables();
    }
    
    public function getFileSystem()
    {
        return $this->fs;
    }
    
    public function getExternalVariables()
    {
        $variables = array();
        
        if(isset($this->parsers[self::EXTERNALS]))
        {
            $variables = $this->parsers[self::EXTERNALS]->getExternalVariables();
        }
        
        return $variables;
    }
    
    private function printExternalFilesStatus()
    {
        $files = $this->getExternalFilesStatus();
        
        foreach($files as $file => $status)
        {
            $message = sprintf(
               'External file %s was %s',
               $file,
               $status['found'] ? 'found' : '<options=bold>not found</options=bold>'
            );
            
            $this->warning($message);
        }
    }
    
    private function getExternalFilesStatus()
    {
        $files = array();
        
        if(isset($this->parsers[self::EXTERNALS]))
        {
            $files = $this->parsers[self::EXTERNALS]->getExternalFilesStatus();
        }
        
        return $files;
    }
    
    public function getGroups()
    {
        $groups = array();
        
        if(isset($this->parsers[self::GROUPS]))
        {
            $groups = $this->parsers[self::GROUPS]->getCollectedGroups();
        }
        
        return $groups;
    }
}