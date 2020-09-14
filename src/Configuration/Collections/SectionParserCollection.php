<?php

declare(strict_types = 1);

namespace Karma\Configuration\Collections;

use Gaufrette\Filesystem;
use Karma\Configuration\Parser;
use Karma\Configuration\Parser\SectionParser;
use Karma\Configuration\Parser\ExternalParser;
use Karma\Configuration\Parser\GroupParser;
use Karma\Configuration\Parser\IncludeParser;
use Karma\Configuration\Parser\VariableParser;

class SectionParserCollection implements \IteratorAggregate
{
    private const
        VARIABLES = 'variables',
        INCLUDES = 'includes',
        EXTERNALS = 'externals',
        GROUPS = 'groups';

    private array
        $parsers;

    public function __construct()
    {
        $this->parsers = [
            self::VARIABLES => new VariableParser(),
            self::INCLUDES => null,
            self::EXTERNALS => null,
            self::GROUPS => null,
        ];
    }

    public function enableIncludeSupport(): void
    {
        if($this->parsers[self::INCLUDES] === null)
        {
            $this->parsers[self::INCLUDES] = new IncludeParser();
        }
    }

    public function enableExternalSupport(Filesystem $fs): void
    {
        if($this->parsers[self::EXTERNALS] === null)
        {
            $this->parsers[self::EXTERNALS] = new ExternalParser(new Parser($fs));
        }
    }

    public function enableGroupSupport(): void
    {
        if($this->parsers[self::GROUPS] === null)
        {
            $this->parsers[self::GROUPS] = new GroupParser();
        }
    }

    public function variables(): VariableParser
    {
        return $this->parsers[self::VARIABLES];
    }

    public function includes(): ?IncludeParser
    {
        return $this->parsers[self::INCLUDES];
    }

    public function externals(): ?ExternalParser
    {
        return $this->parsers[self::EXTERNALS];
    }

    public function groups(): ?GroupParser
    {
        return $this->parsers[self::GROUPS];
    }

    public function has(string $sectionName): bool
    {
        return isset($this->parsers[$sectionName]);
    }

    public function get(string $sectionName): SectionParser
    {
        if(! $this->has($sectionName))
        {
            throw new \RuntimeException('Unknown section name ' . $sectionName);
        }

        return $this->parsers[$sectionName];
    }

    public function getIterator(): \Iterator
    {
        return new \ArrayIterator(
            array_filter($this->parsers)
        );
    }
}

