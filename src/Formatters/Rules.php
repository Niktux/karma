<?php

declare(strict_types = 1);

namespace Karma\Formatters;

use Karma\Formatter;

final class Rules implements Formatter
{
    private array
        $rules;

    public function __construct(array $rules)
    {
        $this->convertRules($rules);
    }

    private function getSpecialValuesMappingTable(): array
    {
        return [
            '<true>' => true,
            '<false>' => false,
            '<null>' => null,
            '<string>' => static function($value) {
                return is_string($value);
            }
        ];
    }

    private function convertRules(array $rules): void
    {
        $this->rules = [];
        $mapping = $this->getSpecialValuesMappingTable();

        foreach($rules as $value => $result)
        {
            $value = trim($value);

            if(is_string($value) && array_key_exists($value, $mapping))
            {
                $result = $this->handleStringFormatting($value, $result);
                $value = $mapping[$value];
            }

            $this->rules[] = [$value, $result];
        }
    }

    private function handleStringFormatting(string $value, mixed $result): mixed
    {
        if($value === '<string>')
        {
            $result = static function ($value) use ($result) {
                return str_replace('<string>', $value, $result);
            };
        }

        return $result;
    }

    public function format(mixed $value): mixed
    {
        foreach($this->rules as $rule)
        {
            [$condition, $result] = $rule;

            if($this->isRuleMatches($condition, $value))
            {
                return $this->applyFormattingRule($result, $value);
            }
        }

        return $value;
    }

    private function isRuleMatches(mixed $condition, mixed $value): bool
    {
        $hasMatched = ($condition === $value);

        if($condition instanceof \Closure)
        {
            $hasMatched = $condition($value);
        }

        return $hasMatched;
    }

    private function applyFormattingRule(mixed $ruleResult, mixed $value): mixed
    {
        if($ruleResult instanceof \Closure)
        {
            return $ruleResult($value);
        }

        return $ruleResult;
    }
}
