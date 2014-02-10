<?php

namespace Karma;

use Karma\VCS\Vcs;
use Psr\Log\NullLogger;

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

    public function execute($rootPath = '.')
    {
        $rootPath = $this->sanitizeDirectory($rootPath);
        
        $it = $this->finder->findFiles("~$this->suffix$~");
        $suffixLength = strlen($this->suffix);
        
        foreach($it as $distFile)
        {
            $targetFile = $rootPath . substr($distFile, 0, $suffixLength * -1);
            
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
    
    private function sanitizeDirectory($rootPath)
    {
        $directorySeparator = '/';
        $rootPath = rtrim($rootPath, $directorySeparator);

        if($rootPath === '.')
        {
            $rootPath = '';
        }
        
        if(! empty($rootPath))
        {
            $rootPath .= $directorySeparator;
        }
        
        return $rootPath;
    }
}