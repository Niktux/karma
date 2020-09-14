<?php

declare(strict_types = 1);

namespace Karma\Generator;

use Karma\Configuration\FileParser;
use Karma\Generator\NameTranslators\NullTranslator;

class VariableProvider
{
    private FileParser
        $parser;
    private NameTranslator
        $nameTranslator;

    public function __construct(FileParser $parser)
    {
        $this->parser = $parser;
        $this->nameTranslator = new NullTranslator();
    }

    public function setNameTranslator(NameTranslator $translator)
    {
        $this->nameTranslator = $translator;

        return $this;
    }

    public function getAllVariables()
    {
        $parsedVariables = $this->parser->getVariables();

        $variables = array();

        foreach($parsedVariables as $variable => $info)
        {
            $variables[$variable] = $this->nameTranslator->translate($info['file'], $variable);
        }

        return $variables;
    }
}
