<?php

namespace Karma\Configuration\Parser;

use Karma\Configuration\Parser;
use Gaufrette\Filesystem;

class ExternalParser extends AbstractGroupParser
{
    private
        $parser;
    
    public function __construct(Parser $parser)
    {
        parent::__construct();

        $this->parser = $parser;
    }
    
    public function parse($line)
    {
        $file = trim($line);
    }
}