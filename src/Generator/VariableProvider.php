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

    public function setNameTranslator(NameTranslator $translator): void
    {
        $this->nameTranslator = $translator;
    }

    public function getAllVariables(): array
    {
        $parsedVariables = $this->parser->getVariables();

        $variables = [];

        foreach($parsedVariables as $variable => $info)
        {
            $variables[$variable] = $this->nameTranslator->translate($info['file'], $variable);
        }

        return $variables;
    }
}
