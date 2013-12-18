<?php

namespace Karma\Configuration\Parser;

interface GroupParser
{
    public function parse($line);
    public function setCurrentFile($filePath);
}