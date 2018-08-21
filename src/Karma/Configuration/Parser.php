<?php

declare(strict_types = 1);

namespace Karma\Configuration;

use Gaufrette\Filesystem;
use Karma\Configuration\Parser\NullParser;
use Karma\Configuration\Parser\IncludeParser;
use Karma\Configuration\Parser\VariableParser;
use Karma\Configuration\Parser\ExternalParser;
use Psr\Log\NullLogger;
use Karma\Configuration\Parser\GroupParser;

class Parser implements FileParser
{
    use \Karma\Logging\LoggerAware;

    const
        INCLUDES = 'includes',
        VARIABLES = 'variables',
        EXTERNALS = 'externals',
        GROUPS = 'groups';

    private
        $parsers,
        $currentParser,
        $parsedFiles,
        $fs,
        $eol;

    public function __construct(Filesystem $fs)
    {
        $this->logger = new NullLogger();

        $this->parsers = [
            self::VARIABLES => new VariableParser(),
        ];

        $this->parsedFiles = [];
        $this->fs = $fs;
        $this->eol = "\n";
    }

    public function setEOL(string $eol): self
    {
        $this->eol = $eol;

        return $this;
    }

    public function enableIncludeSupport(): self
    {
        if(! isset($this->parsers[self::INCLUDES]))
        {
            $this->parsers[self::INCLUDES] = new IncludeParser();
        }

        return $this;
    }

    public function enableExternalSupport(): self
    {
        if(! isset($this->parsers[self::EXTERNALS]))
        {
            $this->parsers[self::EXTERNALS] = new ExternalParser(new Parser($this->fs));
        }

        return $this;
    }

    public function enableGroupSupport(): self
    {
        if(! isset($this->parsers[self::GROUPS]))
        {
            $this->parsers[self::GROUPS] = new GroupParser();
        }

        return $this;
    }

    public function parse(string $masterFilePath): array
    {
        try
        {
            $this->parseFromMasterFile($masterFilePath);

            $variables = $this->getVariables();
            $this->printExternalFilesStatus();

            $this->postParse();

            return $variables;
        }
        catch(\RuntimeException $e)
        {
            $this->error($e->getMessage());

            throw $e;
        }
    }

    private function parseFromMasterFile(string $masterFilePath): void
    {
        $files = [$masterFilePath];

        while(! empty($files))
        {
            foreach($files as $file)
            {
                $this->readFile($file);
            }

            if(isset($this->parsers[self::INCLUDES]))
            {
                $includeParser = $this->parsers[self::INCLUDES];
                $files = $includeParser->getCollectedFiles();
            }

            // Avoid loop
            $files = array_diff($files, $this->parsedFiles);
        }
    }

    private function readFile(string $filePath): void
    {
        $lines = $this->extractLines($filePath);
        $this->changeCurrentFile($filePath);

        $this->currentParser = new NullParser();
        $currentLineNumber = 0;

        foreach($lines as $line)
        {
            $currentLineNumber++;

            if(empty($line))
            {
                continue;
            }

            $sectionName = $this->extractSectionName($line);
            if($sectionName !== null)
            {
                $this->switchSectionParser($sectionName);

                continue;
            }

            $this->currentParser->parse($line, $currentLineNumber);
        }

        $this->parsers[self::VARIABLES]->endOfFileCheck();
    }

    private function extractLines(string $filePath): array
    {
        if(! $this->fs->has($filePath))
        {
            throw new \RuntimeException("$filePath does not exist");
        }

        $content = $this->fs->read($filePath);

        $lines = explode($this->eol, $content ?? '');
        $lines = $this->trimLines($lines);

        $this->parsedFiles[] = $filePath;

        if(empty($lines))
        {
            $this->warning("Empty file ($filePath)");
        }

        return $lines;
    }

    private function trimLines(array $lines): array
    {
        return array_map('trim', $lines);
    }

    private function changeCurrentFile(string $filePath): void
    {
        $this->info("Reading $filePath");

        foreach($this->parsers as $parser)
        {
            $parser->setCurrentFile($filePath);
        }
    }

    private function extractSectionName(string $line): ?string
    {
        $sectionName = null;

        // [.*]
        if(preg_match('~^\[(?P<groupName>[^\]]+)\]$~', $line, $matches))
        {
            $sectionName = trim(strtolower($matches['groupName']));
        }

        return $sectionName;
    }

    private function switchSectionParser(string $sectionName): void
    {
        if(! isset($this->parsers[$sectionName]))
        {
            throw new \RuntimeException('Unknown section name ' . $sectionName);
        }

        $this->currentParser = $this->parsers[$sectionName];
    }

    public function getVariables(): array
    {
        return $this->parsers[self::VARIABLES]->getVariables();
    }

    public function getFileSystem(): Filesystem
    {
        return $this->fs;
    }

    public function getExternalVariables(): array
    {
        $variables = [];

        if(isset($this->parsers[self::EXTERNALS]))
        {
            $variables = $this->parsers[self::EXTERNALS]->getExternalVariables();
        }

        return $variables;
    }

    private function printExternalFilesStatus(): void
    {
        $files = $this->getExternalFilesStatus();

        foreach($files as $file => $status)
        {
            if($status['found'] === false)
            {
                $this->warning(sprintf(
                   'External file %s was not found',
                   $file
                ));
            }
        }
    }

    private function getExternalFilesStatus(): array
    {
        $files = [];

        if(isset($this->parsers[self::EXTERNALS]))
        {
            $files = $this->parsers[self::EXTERNALS]->getExternalFilesStatus();
        }

        return $files;
    }

    public function getGroups(): array
    {
        $groups = [];

        if(isset($this->parsers[self::GROUPS]))
        {
            $groups = $this->parsers[self::GROUPS]->getCollectedGroups();
        }

        return $groups;
    }

    private function postParse(): void
    {
        foreach($this->parsers as $parser)
        {
            $parser->postParse();
        }
    }

    public function isSystem(string $variableName): bool
    {
        $system = false;

        $variables = $this->getVariables();
        if(isset($variables[$variableName]))
        {
            $system = $variables[$variableName]['system'];
        }

        return $system;
    }

    public function getDefaultEnvironmentsForGroups(): array
    {
         $defaultEnvironments = [];

        if(isset($this->parsers[self::GROUPS]))
        {
            $defaultEnvironments = $this->parsers[self::GROUPS]->getDefaultEnvironmentsForGroups();
        }

        return $defaultEnvironments;
    }
}
