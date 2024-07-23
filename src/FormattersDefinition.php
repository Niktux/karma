<?php

declare(strict_types = 1);

namespace Karma;

interface FormattersDefinition
{
    public const string
        DEFAULT_FORMATTER_NAME = 'default';

    public function defaultFormatterName(): ?string;
    public function formatters();
    public function fileExtensionFormatters();
}
