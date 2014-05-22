<?php

namespace Karma;

interface FormattersDefinition
{
    const
        DEFAULT_FORMATTER_NAME = 'default';
    
    public function getDefaultFormatterName();
    public function getFormatters();
    public function getFileExtensionFormatters();    
}