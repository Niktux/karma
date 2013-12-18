<?php

namespace Karma\Configuration;

use Gaufrette\Filesystem;
use Karma\Configuration\Parser\NullParser;
use Karma\Configuration\Parser\IncludeParser;
use Karma\Configuration\Parser\VariableParser;
use Psr\Log\NullLogger;

class Parser
{
    use \Karma\LoggerAware;
    
    const
        INCLUDES = 'includes',
        VARIABLES = 'variables';
    
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
            self::INCLUDES => new IncludeParser(),
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
    
    public function parse($masterFilePath)
    {
        try
        {
            $this->parseFromMasterFile($masterFilePath);
            
            return $this->getVariables();
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
            
            $includeParser = $this->parsers[self::INCLUDES];
            $files = $includeParser->getCollectedFiles();
            
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
            $groupName = $this->extractGroupName($line);
            if($groupName !== null)
            {
                $this->switchGroupParser($groupName);
                continue;
            }

            $this->currentParser->parse($line);
        }
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
    
    private function extractGroupName($line)
    {
        $groupName = null;
        
        // [.*]
        if(preg_match('~^\[(?P<groupName>[^\]]+)\]$~', $line, $matches))
        {
            $groupName = trim(strtolower($matches['groupName']));
        }
        
        return $groupName;
    }
    
    private function switchGroupParser($groupName)
    {
        if(! isset($this->parsers[$groupName]))
        {
            throw new \RuntimeException('Unknown group name ' . $groupName);
        }
        
        $this->currentParser = $this->parsers[$groupName];
    }
    
    private function getVariables()
    {
        return $this->parsers[self::VARIABLES]->getVariables();
    }
}