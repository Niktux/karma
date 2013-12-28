<?php

namespace Karma\Configuration\Parser;

use Karma\Configuration\Parser;
use Gaufrette\Filesystem;

class ExternalParser extends AbstractGroupParser
{
    private
        $parser,
        $variables;
    
    public function __construct(Parser $parser)
    {
        parent::__construct();

        $this->parser = $parser;
        $this->variables = array();
    }
    
    public function parse($line)
    {
        $file = trim($line);
        
        if($this->parser->getFileSystem()->has($file))
        {
            $this->variables = $this->parser->parse($file);
        }
    }
    
    public function getExternalVariables()
    {
        return $this->variables;
    }
}