<?php

declare(strict_types = 1);

namespace Karma\Generator\ConfigurationFileGenerators;

use Gaufrette\Filesystem;
use Karma\Configuration;
use Karma\Generator\VariableProvider;
use Symfony\Component\Yaml\Yaml;
use Karma\Application;

class YamlGenerator extends AbstractFileGenerator
{
    private array
        $files;

    public function __construct(Filesystem $fs, Configuration $reader, VariableProvider $variableProvider)
    {
        parent::__construct($fs, $reader, $variableProvider);

        $this->files = [];
    }

    protected function generateVariable(string $variableName, $value): void
    {
        if(stripos($variableName, self::DELIMITER) === false)
        {
            throw new \RuntimeException(sprintf(
               'Variable %s does not contain delimiter (%s)',
                $variableName,
                self::DELIMITER
            ));
        }

        $parts = explode(self::DELIMITER, $variableName);
        $current = & $this->files;

        foreach($parts as $part)
        {
            if(! isset($current[$part]))
            {
                $current[$part] = array();
            }

            $current= & $current[$part];
        }

        $current = $value;
    }

    protected function postGenerate(): void
    {
        if($this->dryRun === true)
        {
            return;
        }

        foreach($this->files as $file => $content)
        {
            $filename = $this->computeFilename($file);

            $this->backupFile($filename);
            $this->fs->write($filename, $this->formatContent($content), true);
        }
    }

    private function backupFile(string $filename): void
    {
        if($this->enableBackup === true)
        {
            if($this->fs->has($filename))
            {
                $content = $this->fs->read($filename);
                $backupFilename = $filename . Application::BACKUP_SUFFIX;

                $this->fs->write($backupFilename, $content, true);
            }
        }
    }

    private function computeFilename(string $file): string
    {
        return "$file.yml";
    }

    private function formatContent($content): string
    {
        return Yaml::dump($content);
    }
}
