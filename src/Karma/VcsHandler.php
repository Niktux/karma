<?php

namespace Karma;

use Karma\VCS\Vcs;
use Psr\Log\NullLogger;
use Gaufrette\Adapter\iterator_to_array;

class VcsHandler
{
    use Logging\LoggerAware;
    
    private
        $vcs,
        $finder,
        $suffix;
    
    public function __construct(Vcs $vcs, Finder $finder)
    {
        $this->logger = new NullLogger();
        $this->vcs = $vcs;
        $this->finder = $finder; 
        $this->suffix = Application::DEFAULT_DISTFILE_SUFFIX;
    }

    public function setSuffix($suffix)
    {
        $this->suffix = $suffix;
    
        return $this;
    }

    public function execute()
    {
        $it = $this->finder->findFiles("~$this->suffix$~");
        $suffixLength = strlen($this->suffix);
        
        foreach($it as $distFile)
        {
            $targetFile = substr($distFile, 0, $suffixLength * -1);
            
            if($this->vcs->isTracked($targetFile))
            {
                $this->logger->info("Untrack $targetFile");
                $this->vcs->untrackFile($targetFile);
            }
            
            if($this->vcs->isIgnored($targetFile) === false)
            {
                $this->logger->info("Ignore $targetFile");
                $this->vcs->ignoreFile($targetFile);
            }
        }
    }
}