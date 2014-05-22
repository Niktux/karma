<?php

namespace Karma\FormatterProviders;

use Karma\FormatterProvider;
use Karma\FormattersDefinition;
use Karma\Formatters\Raw;
use Karma\Formatters\Rules;

class ProfileProvider implements FormatterProvider
{
    private
        $defaultFormatterName,
        $formatters,
        $fileExtensionFormatters;
    
    public function __construct(FormattersDefinition $definition)
    {
        $this->formatters = array(
            FormattersDefinition::DEFAULT_FORMATTER_NAME => new Raw(),
        );
        
        $this->fileExtensionFormatters = array();
        
        $this->defaultFormatterName = $definition->getDefaultFormatterName();
        $this->parseFormatters($definition->getFormatters());
        $this->fileExtensionFormatters = array_map('trim', $definition->getFileExtensionFormatters());    
    }

    private function parseFormatters($content)
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
    
    public function hasFormatter($index)
    {
        return isset($this->formatters[$index]);
    }
    
    public function getFormatter($fileExtension, $index = null)
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
    
    private function getDefaultFormatterName()
    {
        $name = FormattersDefinition::DEFAULT_FORMATTER_NAME;
    
        if($this->hasFormatter($this->defaultFormatterName))
        {
            $name = $this->defaultFormatterName;
        }
    
        return $name;
    }
}