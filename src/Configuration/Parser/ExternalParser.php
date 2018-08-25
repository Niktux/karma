<?php

declare(strict_types = 1);

namespace Karma\Configuration\Parser;

use Karma\Configuration\Parser;

class ExternalParser extends AbstractSectionParser
{
    private
        $parser,
        $variables,
        $filesStatus;

    public function __construct(Parser $parser)
    {
        parent::__construct();

        $this->parser = $parser;
        $this->variables = [];
        $this->filesStatus = [];
    }

    public function parseLine(string $line): void
    {
        $file = trim($line);

        $found = false;
        if($this->parser->getFileSystem()->has($file))
        {
            $found = true;
            $this->variables = $this->parser->parse($file);
        }

        $this->filesStatus[$file] = [
            'found' => $found,
            'referencedFrom' => $this->currentFilePath,
        ];
    }

    public function getExternalVariables(): array
    {
        return $this->variables;
    }

    public function getExternalFilesStatus(): array
    {
        return $this->filesStatus;
    }
}
