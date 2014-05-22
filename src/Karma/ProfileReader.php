<?php

namespace Karma;

use Gaufrette\Filesystem;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

class ProfileReader implements FormattersDefinition
{
    const
        TEMPLATE_SUFFIX_INDEX = 'suffix',
        MASTER_FILENAME_INDEX = 'master',
        CONFIGURATION_DIRECTORY_INDEX = 'confDir',
        SOURCE_PATH_INDEX = 'sourcePath',
        DEFAULT_FORMATTER_INDEX = 'defaultFormatter',
        FORMATTERS_INDEX = 'formatters',
        FILE_EXTENSION_FORMATTERS_INDEX = 'fileExtensionFormatters';
    
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
            self::DEFAULT_FORMATTER_INDEX => self::DEFAULT_FORMATTER_NAME,
            self::FORMATTERS_INDEX => array(),
            self::FILE_EXTENSION_FORMATTERS_INDEX => array()
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
        
        foreach(array_keys($this->attributes) as $name)
        {
            if(isset($values[$name]))
            {
                $this->attributes[$name] = $values[$name];
            }
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
    
    public function getDefaultFormatterName()
    {
        return $this->get(self::DEFAULT_FORMATTER_INDEX);
    }
    
    public function getFormatters()
    {
        return $this->get(self::FORMATTERS_INDEX);
    }
    
    public function getFileExtensionFormatters()
    {
        return $this->get(self::FILE_EXTENSION_FORMATTERS_INDEX);
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
}