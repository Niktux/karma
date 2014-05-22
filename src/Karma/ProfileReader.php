<?php

namespace Karma;

use Gaufrette\Filesystem;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;
use Karma\Formatters\Raw;
use Karma\Formatters\Rules;

class ProfileReader implements FormatterProvider
{
    const
        DEFAULT_FORMATTER_NAME = 'default',
    
        TEMPLATE_SUFFIX_INDEX = 'suffix',
        MASTER_FILENAME_INDEX = 'master',
        CONFIGURATION_DIRECTORY_INDEX = 'confDir',
        SOURCE_PATH_INDEX = 'sourcePath',
        FORMATTERS_INDEX = 'formatters',
        FILE_EXTENSION_FORMATTERS_INDEX = 'fileExtensionFormatters',
        DEFAULT_FORMATTER_INDEX = 'defaultFormatter';
    
    private
        $attributes,
        $formatters,
        $fileExtensionFormatters;
    
    public function __construct(Filesystem $fs)
    {
        $this->attributes = array(
            self::TEMPLATE_SUFFIX_INDEX => null,
            self::MASTER_FILENAME_INDEX => null,
            self::CONFIGURATION_DIRECTORY_INDEX => null,
            self::SOURCE_PATH_INDEX => null,
            self::DEFAULT_FORMATTER_INDEX => self::DEFAULT_FORMATTER_NAME    
        );
        
        $this->formatters = array(
            self::DEFAULT_FORMATTER_NAME => new Raw(),
        );
        
        $this->fileExtensionFormatters = array();

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
        
        foreach(array_keys($this->attributes) as $name)
        {
            if(isset($values[$name]) && is_string($values[$name]))
            {
                $this->attributes[$name] = $values[$name];
            }
        }
        
        if(isset($values[self::FORMATTERS_INDEX]))
        {
            $this->parseFormatters($values[self::FORMATTERS_INDEX]);    
        }
        
        if(isset($values[self::FILE_EXTENSION_FORMATTERS_INDEX]))
        {
            $this->fileExtensionFormatters = array_map('trim', $values[self::FILE_EXTENSION_FORMATTERS_INDEX]);    
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
        return $this->has(self::TEMPLATE_SUFFIX_INDEX);
    }
    
    public function getTemplatesSuffix()
    {
        return $this->get(self::TEMPLATE_SUFFIX_INDEX);
    }
    
    public function hasMasterFilename()
    {
        return $this->has(self::MASTER_FILENAME_INDEX);
    }
    
    public function getMasterFilename()
    {
        return $this->get(self::MASTER_FILENAME_INDEX);
    }
    
    public function hasConfigurationDirectory()
    {
        return $this->has(self::CONFIGURATION_DIRECTORY_INDEX);
    }
    
    public function getConfigurationDirectory()
    {
        return $this->get(self::CONFIGURATION_DIRECTORY_INDEX);
    }
    
    public function hasSourcePath()
    {
        return $this->has(self::SOURCE_PATH_INDEX);
    }
    
    public function getSourcePath()
    {
        return $this->get(self::SOURCE_PATH_INDEX);
    }
    
    private function has($attributeName)
    {
        return isset($this->attributes[$attributeName]);
    }
    
    private function get($attributeName)
    {
        $value = null;

        if($this->has($attributeName))
        {
            $value = $this->attributes[$attributeName];
        }
        
        return $value;
    }
    
    public function hasFormatter($index)
    {
        return isset($this->formatters[$index]);
    }
    
    public function getFormatter($fileExtension, $index = null)
    {
        $formatter = $this->formatters[$this->getDefaultFormatterName()];
        
        if($this->hasFormatter($index))
        {
            $formatter = $this->formatters[$index];    
        }    
        
        return $formatter;
    }
    
    private function getDefaultFormatterName()
    {
        $name = self::DEFAULT_FORMATTER_NAME;
        
        $defaultFormatterName = $this->get(self::DEFAULT_FORMATTER_INDEX);
        
        if($this->hasFormatter($defaultFormatterName))
        {
            $name = $defaultFormatterName;
        }
        
        return $name;
    }
}