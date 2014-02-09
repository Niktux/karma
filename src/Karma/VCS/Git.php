<?php

namespace Karma\VCS;

use GitWrapper\GitWrapper;

class Git implements Vcs
{
    private
        $git,
        $trackedFiles;
    
    public function __construct($rootDirectory)
    {
        $wrapper = new GitWrapper();
        $this->git = $wrapper->workingCopy($rootDirectory);
        
        $this->trackedFiles = null;
    }
    
    public function isTracked($filepath)
    {
        if($this->trackedFiles === null)
        {
            $this->trackedFiles = $this->getTrackedFiles();
        }    
        
        return in_array($filepath, $this->trackedFiles);
    }
    
    private function getTrackedFiles()
    {
        $this->git->run(array(
            'ls-files'
        ));

        return explode(PHP_EOL, $this->git->getOutput());
    }
    
    public function untrackFile($filepath)
    {
        $this->git->run(array(
        	'rm',
            '--cached',
            $filepath
        ));
    }
    
    public function isIgnored($filepath)
    {
    
    }
        
    public function ignoreFile($filepath)
    {
        
    }
}