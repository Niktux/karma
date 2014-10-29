<?php

namespace Karma\Generator;

use Karma\Configuration\FileParser;
use Karma\Generator\NameTranslators\NullTranslator;

class VariableProvider
{
    private
        $parser,
        $masterFilePath,
        $nameTranslator;

    public function __construct(FileParser $parser, $masterFilePath)
    {
        $this->parser = $parser;
        $this->masterFilePath = $masterFilePath;
        $this->nameTranslator = new NullTranslator();
    }

    public function setNameTranslator(NameTranslator $translator)
    {
        $this->nameTranslator = $translator;

        return $this;
    }

    public function getAllVariables()
    {
        $parsedVariables = $this->parser->parse($this->masterFilePath);

        $variables = array();

        foreach($parsedVariables as $variable => $info)
        {
            $variables[$variable] = $this->nameTranslator->translate($info['file'], $variable);
        }

        return $variables;
    }
}
