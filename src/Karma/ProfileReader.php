<?php

namespace Karma;

use Gaufrette\Filesystem;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

class ProfileReader
{
    private
        $templatesSuffix,
        $masterFilename,
        $configurationDirectory;
    
    public function __construct(Filesystem $fs)
    {
        $this->templatesSuffix = null;
        $this->masterFilename = null;
        $this->configurationDirectory = null;

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
}