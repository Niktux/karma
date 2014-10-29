<?php

namespace Karma\Configuration;

interface FileParser
{
    /**
     * @param string $masterFilePath
     * @return array $variables
     */
    public function parse($masterFilePath);
}