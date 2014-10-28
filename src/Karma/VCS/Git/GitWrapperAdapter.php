<?php

namespace Karma\VCS\Git;

use GitWrapper\GitWrapper;

class GitWrapperAdapter implements CommandLauncher
{
    private
        $git;

    public function __construct()
    {
        $this->git = null;
    }

    public function initialize($rootDirectory)
    {
        $wrapper = new GitWrapper();
        $this->git = $wrapper->workingCopy($rootDirectory);
    }

    public function run($args, $setDirectory = true)
    {
        if($this->git === null)
        {
            throw new \RuntimeException('Git repository is not initialized');
        }

        $this->git->run($args, $setDirectory);

        return $this->git->getOutput();
    }
}
