<?php

declare(strict_types = 1);

namespace Karma\Configuration;

use Gaufrette\Filesystem;
use Karma\Configuration\Collections\SectionParserCollection;
use Karma\Configuration\Parser\NullParser;
use Karma\Configuration\Parser\SectionParser;
use Karma\Logging\LoggerAware;
use Psr\Log\NullLogger;

final class Parser implements FileParser
{
    use LoggerAware;

    private SectionParserCollection
        $parsers;
    private ?SectionParser
        $currentParser;
    private array
        $parsedFiles;
    private Filesystem
        $fs;
    private string
        $eol;

    public function __construct(Filesystem $fs)
    {
        $this->logger = new NullLogger();
        $this->parsers = new SectionParserCollection();

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
        $this->parsers->enableIncludeSupport();

        return $this;
    }

    public function enableExternalSupport(): self
    {
       $this->parsers->enableExternalSupport($this->fs);

        return $this;
    }

    public function enableGroupSupport(): self
    {
        $this->parsers->enableGroupSupport();

        return $this;
    }

    public function parse(string $masterFilePath): array
    {
        try
        {
            $this->parseFromMasterFile($masterFilePath);

            $variables = $this->variables();
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
                $this->parseFile($file);
            }

            $parser = $this->parsers->includes();
            if($parser !== null)
            {
                $files = $parser->collectedFiles();
            }

            // Avoid loop
            $files = array_diff($files, $this->parsedFiles);
        }
    }

    private function parseFile(string $filePath): void
    {
        $this->parsedFiles[] = $filePath;
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

        $this->parsers->variables()->endOfFileCheck();
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
        if(preg_match('~^\[(?P<sectionName>[^\]]+)\]$~', $line, $matches))
        {
            $sectionName = strtolower(trim($matches['sectionName']));
        }

        return $sectionName;
    }

    private function switchSectionParser(string $sectionName): void
    {
        $this->currentParser = $this->parsers->get($sectionName);
    }

    public function variables(): array
    {
        return $this->parsers->variables()->variables();
    }

    public function fileSystem(): Filesystem
    {
        return $this->fs;
    }

    public function externalVariables(): array
    {
        $variables = [];

        $parser = $this->parsers->externals();
        if($parser !== null)
        {
            $variables = $parser->externalVariables();
        }

        return $variables;
    }

    private function printExternalFilesStatus(): void
    {
        $files = $this->externalFilesStatus();

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

    private function externalFilesStatus(): array
    {
        $files = [];

        $parser = $this->parsers->externals();
        if($parser !== null)
        {
            $files = $parser->externalFilesStatus();
        }

        return $files;
    }

    public function groups(): array
    {
        $groups = [];

        $parser = $this->parsers->groups();
        if($parser !== null)
        {
            $groups = $parser->collectedGroups();
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

        $variables = $this->variables();
        if(isset($variables[$variableName]))
        {
            $system = $variables[$variableName]['system'];
        }

        return $system;
    }

    public function defaultEnvironmentsForGroups(): array
    {
        $defaultEnvironments = [];

        $parser = $this->parsers->groups();
        if($parser !== null)
        {
            $defaultEnvironments = $parser->defaultEnvironmentsForGroups();
        }

        return $defaultEnvironments;
    }
}
