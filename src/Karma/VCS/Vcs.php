<?php

namespace Karma\VCS;

interface Vcs
{
    /**
     * True if given filepath is under version control
     *
     * @param string $filepath
     *
     * @return boolean
     */
    public function isTracked($filepath);

    /**
     * Remove file from version control
     *
     * @param string $filepath
     */
    public function untrackFile($filepath);

    /**
     * True if given filepath is already in vcs ignore list
     *
     * @param string $filepath
     *
     * @return boolean
     */
    public function isIgnored($filepath);

    /**
     * Add file to vcs ignore list
     *
     * @param string $filepath
     */
    public function ignoreFile($filepath);
}
