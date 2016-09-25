<?php

namespace Karma\VCS\Git;

interface CommandLauncher
{
    /**
     *
     * @param string $rootDirectory
     */
    public function initialize($rootDirectory);

    /**
     * Runs a Git command
     *
     * @param array $args The arguments passed to the command method.
     * @param boolean $setDirectory Set the working directory
     *
     * @return output
     */
    public function run($args, $setDirectory = true);
}
