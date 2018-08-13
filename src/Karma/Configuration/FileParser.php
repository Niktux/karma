<?php

declare(strict_types = 1);

namespace Karma\Configuration;

interface FileParser
{
    /**
     * @return array $variables
     */
    public function getVariables();
}
