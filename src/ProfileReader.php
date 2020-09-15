<?php

declare(strict_types = 1);

namespace Karma;

use Gaufrette\Filesystem;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

class ProfileReader implements FormattersDefinition
{
    private const
        TEMPLATE_SUFFIX_INDEX = 'suffix',
        MASTER_FILENAME_INDEX = 'master',
        CONFIGURATION_DIRECTORY_INDEX = 'confDir',
        SOURCE_PATH_INDEX = 'sourcePath',
        TARGET_PATH_INDEX = 'targetPath',
        DEFAULT_FORMATTER_INDEX = 'defaultFormatter',
        FORMATTERS_INDEX = 'formatters',
        FILE_EXTENSION_FORMATTERS_INDEX = 'fileExtensionFormatters',
        GENERATOR_INDEX = 'generator';

    private array
        $attributes;

    public function __construct(Filesystem $fs)
    {
        $this->attributes = [
            self::TEMPLATE_SUFFIX_INDEX => null,
            self::MASTER_FILENAME_INDEX => null,
            self::CONFIGURATION_DIRECTORY_INDEX => null,
            self::SOURCE_PATH_INDEX => null,
            self::TARGET_PATH_INDEX => null,
            self::DEFAULT_FORMATTER_INDEX => self::DEFAULT_FORMATTER_NAME,
            self::FORMATTERS_INDEX => array(),
            self::FILE_EXTENSION_FORMATTERS_INDEX => array(),
            self::GENERATOR_INDEX => array(),
        ];

        $this->read($fs);
    }

    private function read(Filesystem $fs): void
    {
        $profileFilename = Application::PROFILE_FILENAME;

        if($fs->has($profileFilename))
        {
            $this->processProfileContent($fs->read($profileFilename));
        }
    }

    private function processProfileContent($content): void
    {
        try
        {
            $values = Yaml::parse($content);
        }
        catch(ParseException $e)
        {
            throw new \RuntimeException(sprintf(
               'Error while parsing profile : %s',
                $e->getMessage()
            ));
        }

        foreach(array_keys($this->attributes) as $name)
        {
            if(! isset($values[$name]))
            {
                continue;
            }

            $this->ensureParameterFormatIsValid($name, $values[$name]);

            $this->attributes[$name] = $values[$name];
        }
    }

    public function hasTemplatesSuffix(): bool
    {
        return $this->hasString(self::TEMPLATE_SUFFIX_INDEX);
    }

    public function getTemplatesSuffix()
    {
        return $this->getString(self::TEMPLATE_SUFFIX_INDEX);
    }

    public function hasMasterFilename(): bool
    {
        return $this->hasString(self::MASTER_FILENAME_INDEX);
    }

    public function getMasterFilename()
    {
        return $this->getString(self::MASTER_FILENAME_INDEX);
    }

    public function hasConfigurationDirectory(): bool
    {
        return $this->hasString(self::CONFIGURATION_DIRECTORY_INDEX);
    }

    public function getConfigurationDirectory()
    {
        return $this->getString(self::CONFIGURATION_DIRECTORY_INDEX);
    }

    public function hasSourcePath(): bool
    {
        return $this->has(self::SOURCE_PATH_INDEX);
    }

    public function getSourcePath()
    {
        return $this->get(self::SOURCE_PATH_INDEX);
    }

    public function hasTargetPath(): bool
    {
        return $this->has(self::TARGET_PATH_INDEX);
    }

    public function getTargetPath()
    {
        return $this->get(self::TARGET_PATH_INDEX);
    }

    public function getDefaultFormatterName(): ?string
    {
        return $this->getString(self::DEFAULT_FORMATTER_INDEX);
    }

    public function getFormatters()
    {
        return $this->get(self::FORMATTERS_INDEX);
    }

    public function getFileExtensionFormatters()
    {
        return $this->get(self::FILE_EXTENSION_FORMATTERS_INDEX);
    }

    public function getGeneratorOptions()
    {
        return $this->get(self::GENERATOR_INDEX);
    }

    private function has(string $attributeName): bool
    {
        return isset($this->attributes[$attributeName]);
    }

    private function hasString(string $attributeName): bool
    {
        return isset($this->attributes[$attributeName]) && is_string($this->attributes[$attributeName]);
    }

    private function get(string $attributeName)
    {
        $value = null;

        if($this->has($attributeName))
        {
            $value = $this->attributes[$attributeName];
        }

        return $value;
    }

    private function getString(string $attributeName): ?string
    {
        $value = null;

        if($this->hasString($attributeName))
        {
            $value = $this->attributes[$attributeName];
        }

        return $value;
    }

    private function ensureParameterFormatIsValid($parameter, $value): void
    {
        $parameterValidators = [
            'targetPath' => static function($value) {
                return is_string($value);
            }
        ];

        if(
            ! array_key_exists($parameter, $parameterValidators)
            || ! $parameterValidators[$parameter] instanceof \Closure
        )
        {
            return;
        }

        if(! $parameterValidators[$parameter]($value))
        {
            throw new \RuntimeException('Parameter %s format is invalid');
        }
    }
}
