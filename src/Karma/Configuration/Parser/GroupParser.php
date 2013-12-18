<?php

namespace Karma\Configuration\Parser;

use Psr\Log\LoggerInterface;

interface GroupParser
{
    public function parse($line);
    public function setCurrentFile($filePath);
    public function setLogger(LoggerInterface $logger);
}