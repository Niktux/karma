<?php

declare(strict_types = 1);

namespace Karma\Configuration\Parser;

use Karma\Configuration\Parser;

final class ExternalParser extends AbstractSectionParser
{
    private Parser
        $parser;
    private array
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
        if($this->parser->fileSystem()->has($file))
        {
            $found = true;
            $this->variables = $this->parser->parse($file);
        }

        $this->filesStatus[$file] = [
            'found' => $found,
            'referencedFrom' => $this->currentFilePath,
        ];
    }

    public function externalVariables(): array
    {
        return $this->variables;
    }

    public function externalFilesStatus(): array
    {
        return $this->filesStatus;
    }
}
