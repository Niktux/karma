<?php

declare(strict_types = 1);

namespace Karma\Configuration;

class ValueFilterIterator extends \FilterIterator
{
    use \Karma\Configuration\FilterInputVariable;

    const
        FILTER_WILDCARD = '*',
        ESCAPED_WILDCARD = '**';

    private
        $isRegex,
        $filter;

    public function __construct($filter, \Iterator $iterator)
    {
        parent::__construct($iterator);

        $filter = $this->filterValue($filter);

        $this->isRegex = false;
        if(stripos((string) $filter, self::FILTER_WILDCARD) !== false)
        {
            $this->isRegex = true;
            $filter = $this->convertToRegex($filter);
        }

        $this->filter = $filter;
    }

    public function accept(): bool
    {
        $value = $this->getInnerIterator()->current();

        return $this->acceptValue($value);
    }

    private function acceptValue($value): bool
    {
        if(is_array($value))
        {
            return $this->acceptArray($value);
        }

        if($this->isRegex === true)
        {
            return (bool) preg_match($this->filter, (string) $value);
        }

        return $value === $this->filter;
    }

    private function acceptArray($value): bool
    {
        foreach($value as $oneValue)
        {
            if($this->acceptValue($oneValue))
            {
                return true;
            }
        }

        return false;
    }

    private function convertToRegex(string $filter): string
    {
        $filter = $this->escapeRegexSpecialCharacters($filter);

        $escapedWildcardMarker = '@@@KARMA:ESCAPED_WILDCARD@@@';

        $filter = str_replace(self::ESCAPED_WILDCARD, $escapedWildcardMarker, $filter);
        $filter = str_replace(self::FILTER_WILDCARD, '.*', $filter);
        $filter = str_replace($escapedWildcardMarker, "\\*", $filter);

        return "~^$filter$~";
    }

    private function escapeRegexSpecialCharacters($filter): string
    {
        return strtr($filter, array(
            '.' => '\.',
            '?' => '\?',
            '+' => '\+',
            '[' => '\[',
            ']' => '\]',
            '(' => '\(',
            ')' => '\)',
            '{' => '\{',
            '}' => '\}',
        ));
    }
}
