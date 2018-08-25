<?php

declare(strict_types = 1);

namespace Karma\Configuration\Collections;
// Do not forget to add use statement for Parser

class ParserCollection implements \IteratorAggregate, \Countable
{
    private
        $parsers;

    public function __construct(iterable $parsers = [])
    {
        $this->parsers = [];

        foreach($parsers as $parser)
        {
            if($parser instanceof Parser)
            {
                $this->add($parser);
            }
        }
    }

    public function add(Parser $parser): self
    {
        $this->parsers[] = $parser;

        return $this;
    }

    public function getIterator(): \Iterator
    {
        return new \ArrayIterator($this->parsers);
    }

    public function count(): int
    {
        return count($this->parsers);
    }
}

