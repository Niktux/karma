<?php

namespace Karma\Configuration;

interface FileParser
{
    /**
     * @return array $variables
     */
    public function getVariables();
}