<?php

declare(strict_types = 1);

namespace Karma\Configuration;

trait FilterInputVariable
{
    private function filterValue($value)
    {
        if(is_array($value))
        {
            return array_map(function ($item) {
                return $this->filterOneValue($item);
            }, $value);
        }

        return $this->filterOneValue($value);
    }

    private function filterOneValue($value)
    {
        if(! is_string($value))
        {
            return $value;
        }

        $value = trim($value);

        $knowValues = [
            'true' => true,
            'false' => false,
            'null' => null
        ];

        if(array_key_exists(strtolower($value), $knowValues))
        {
            return $knowValues[strtolower($value)];
        }

        if(is_numeric($value))
        {
            if(strpos($value, '.') !== false && (float) $value == $value)
            {
                return (float) $value;
            }

            return (int) $value;
        }

        return $value;
    }

    public function parseList(string $value)
    {
        $value = trim($value);

        if(preg_match('~^\[(?P<valueList>[^\[\]]*)\]$~', $value, $matches))
        {
            $value = array_map('trim', explode(',', $matches['valueList']));
            $value = $this->removeEmptyEntries($value);
        }

        return $value;
    }

    private function removeEmptyEntries(array $list): array
    {
        return array_merge(array_filter($list, static function($item): bool {
            return is_numeric($item) === true || empty($item) === false;
        }));
    }
}
