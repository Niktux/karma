<?php

declare(strict_types = 1);

namespace Karma\Configuration;

final class ValueFilterIterator extends \FilterIterator
{
    use FilterInputVariable;

    private const string
        FILTER_WILDCARD = '*',
        ESCAPED_WILDCARD = '**';

    private bool
        $isRegex;

    private
        $filter;

    public function __construct($filter, \Iterator $iterator)
    {
        parent::__construct($iterator);

        $filter = $this->filterValue($filter);

        $this->isRegex = false;
        if(str_contains((string) $filter, self::FILTER_WILDCARD))
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

    private function acceptArray(array $value): bool
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

    private function escapeRegexSpecialCharacters(string $filter): string
    {
        return strtr($filter, [
            '.' => '\.',
            '?' => '\?',
            '+' => '\+',
            '[' => '\[',
            ']' => '\]',
            '(' => '\(',
            ')' => '\)',
            '{' => '\{',
            '}' => '\}',
        ]);
    }
}
