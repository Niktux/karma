<?php

declare(strict_types = 1);

namespace Karma\Configuration;

trait FilterInputVariable
{
    private function filterValue(string|array|null $value): array|string|float|int|bool|null
    {
        if(is_array($value))
        {
            return array_map(function ($item) {
                return $this->filterOneValue($item);
            }, $value);
        }

        return $this->filterOneValue($value);
    }

    private function filterOneValue(?string $value): string|float|int|bool|null
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

    public function parseList(string $value): string|array
    {
        $value = trim($value);

        if(preg_match('~^\[(?P<valueList>[^\[\]]*)\]$~', $value, $matches))
        {
            return $this->removeEmptyEntries(
                array_map('trim', explode(',', $matches['valueList']))
            );
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
