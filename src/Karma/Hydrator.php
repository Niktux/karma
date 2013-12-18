<?php

namespace Karma;

use Gaufrette\Filesystem;

class Hydrator
{
    use \Karma\Logging\OutputAware;
    
    private
        $sources,
        $suffix,
        $reader;
    
    public function __construct(Filesystem $sources, $suffix, Configuration $reader)
    {
        $this->sources = $sources;
        $this->suffix = $suffix;
        $this->reader = $reader;
    }
    
    public function hydrate($environment)
    {
        $distFiles = $this->collectDistFiles();
        
        foreach($distFiles as $file)
        {
            $this->hydrateFile($file, $environment);
        }
    }
    
    private function collectDistFiles()
    {
        $finder = new Finder($this->sources);
        
        return $finder->findFiles($this->suffix);
    }
    
    private function hydrateFile($file, $environment)
    {
        $this->debug($file, true);
        
        $content = $this->sources->read($file);
        $targetContent = $this->injectValues($content, $environment);
        
        $targetFile = substr($file, 0, strlen($this->suffix) * -1); 
        $this->sources->write($targetFile, $targetContent);
    }
    
    private function injectValues($content, $environment)
    {
        return preg_replace_callback('~<%(?P<variableName>\w+)%>~', function(array $matches) use($environment){
            return $this->reader->read($matches['variableName'], $environment);
        }, $content);
    }
}