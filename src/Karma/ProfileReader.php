<?php

namespace Karma;

use Gaufrette\Filesystem;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;
use Karma\Formatters\Raw;
use Karma\Formatters\Rules;

class ProfileReader
{
    const
        DEFAULT_FORMATTER_INDEX = 'default';
    
    private
        $templatesSuffix,
        $masterFilename,
        $configurationDirectory,
        $formatters;
    
    public function __construct(Filesystem $fs)
    {
        $this->templatesSuffix = null;
        $this->masterFilename = null;
        $this->configurationDirectory = null;
        $this->formatters = array(
            self::DEFAULT_FORMATTER_INDEX => new Raw(),
        );

        $this->read($fs);
    }
    
    private function read(Filesystem $fs)
    {
        $profileFilename = Application::PROFILE_FILENAME; 
        
        if($fs->has($profileFilename))
        {
            $this->processProfileContent($fs->read($profileFilename));
        }    
    }
    
    private function processProfileContent($content)
    {
        try
        {
            $values = Yaml::parse($content);
        }
        catch(ParseException $e)
        {
            throw new \RuntimeException(sprintf(
               'Error while parsing profile : %s',
                $e->getMessage()
            ));            
        }
        
        if(isset($values['suffix']))
        {
            $this->templatesSuffix = $values['suffix'];
        }
        
        if(isset($values['master']))
        {
            $this->masterFilename = $values['master'];
        }
        
        if(isset($values['confDir']))
        {
            $this->configurationDirectory = $values['confDir'];
        }
        
        if(isset($values['formatters']))
        {
            $this->parseFormatters($values['formatters']);    
        }
    }
    
    private function parseFormatters($content)
    {
        if(! is_array($content))
        {
            throw new \InvalidArgumentException('Syntax error in profile [formatters]');
        }
        
        foreach($content as $name => $rules)
        {
            if(! is_array($rules))
            {
                throw new \InvalidArgumentException('Syntax error in profile [formatters]');
            }
            
            $this->formatters[$name] = new Rules($rules);
        }
    }
    
    public function hasTemplatesSuffix()
    {
        return $this->templatesSuffix !== null;
    }
    
    public function getTemplatesSuffix()
    {
        return $this->templatesSuffix;
    }
    
    public function hasMasterFilename()
    {
        return $this->masterFilename !== null;
    }
    
    public function getMasterFilename()
    {
        return $this->masterFilename;
    }
    
    public function hasConfigurationDirectory()
    {
        return $this->configurationDirectory !== null;
    }
    
    public function getConfigurationDirectory()
    {
        return $this->configurationDirectory;
    }
    
    public function hasFormatter($index)
    {
        return isset($this->formatters[$index]);
    }
    
    public function getFormatter($index = null)
    {
        $formatter = $this->formatters[self::DEFAULT_FORMATTER_INDEX];
        
        if($this->hasFormatter($index))
        {
            $formatter = $this->formatters[$index];    
        }    
        
        return $formatter;
    }
}