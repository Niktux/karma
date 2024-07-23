<?php

declare(strict_types = 1);

namespace Karma\FormatterProviders;

use Karma\Formatter;
use Karma\FormatterProvider;
use Karma\FormattersDefinition;
use Karma\Formatters\Raw;
use Karma\Formatters\Rules;

final class ProfileProvider implements FormatterProvider
{
    private ?string
        $defaultFormatterName;
    private array
        $formatters,
        $fileExtensionFormatters;

    public function __construct(FormattersDefinition $definition)
    {
        $this->formatters = [
            FormattersDefinition::DEFAULT_FORMATTER_NAME => new Raw(),
        ];

        $this->fileExtensionFormatters = [];

        $this->defaultFormatterName = $definition->defaultFormatterName();
        $this->parseFormatters($definition->formatters());
        $this->fileExtensionFormatters = array_map('trim', $definition->fileExtensionFormatters());
    }

    private function parseFormatters($content): void
    {
        if(! is_array($content))
        {
            throw new \InvalidArgumentException('Syntax error in profile [formatters]');
        }

        foreach($content as $name => $rules)
        {
            if(! is_array($rules))
            {
                throw new \InvalidArgumentException('Syntax error in profile [formatters]');
            }

            $this->formatters[trim($name)] = new Rules($rules);
        }
    }

    public function hasFormatter(?string $index): bool
    {
        return isset($this->formatters[$index]);
    }

    public function formatter(?string $fileExtension, ?string $index = null): Formatter
    {
        $formatter = $this->formatters[$this->getDefaultFormatterName()];

        if($index === null)
        {
            if(isset($this->fileExtensionFormatters[$fileExtension]))
            {
                $index = $this->fileExtensionFormatters[$fileExtension];
            }
        }

        if($this->hasFormatter($index))
        {
            $formatter = $this->formatters[$index];
        }

        return $formatter;
    }

    private function getDefaultFormatterName(): string
    {
        $name = FormattersDefinition::DEFAULT_FORMATTER_NAME;

        if($this->hasFormatter($this->defaultFormatterName))
        {
            $name = $this->defaultFormatterName;
        }

        return $name;
    }
}
