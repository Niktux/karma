<?php

namespace Karma\VCS;

use GitWrapper\GitWrapper;
use Gaufrette\Filesystem;

class Git implements Vcs
{
    private
        $git,
        $fs,
        $rootDirectory,
        $trackedFiles;
    
    public function __construct(Filesystem $fs, $rootDirectory)
    {
        $wrapper = new GitWrapper();
        $this->git = $wrapper->workingCopy($rootDirectory);
        
        $this->fs = $fs;
        $this->rootDirectory = $rootDirectory;
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
        if($this->isTracked($filepath) === false)
        {
            return false;
        }
        
        $this->git->run(array(
        	'rm',
            '--cached',
            $filepath
        ));
        
        return true;
    }
    
    public function isIgnored($filepath)
    {
        // TODO find all .gitignore files
        $cwd = getcwd();
        chdir($this->rootDirectory);
        
        $content = file_get_contents('.gitignore');
        var_dump($content);
        
        chdir($cwd);
    }
        
    public function ignoreFile($filepath)
    {
    }
}