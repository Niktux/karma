<?php

declare(strict_types = 1);

namespace Karma;

interface FormattersDefinition
{
    const
        DEFAULT_FORMATTER_NAME = 'default';

    public function getDefaultFormatterName(): ?string;
    public function getFormatters();
    public function getFileExtensionFormatters();
}
