<?php

namespace Karma\Generator\ConfigurationFileGenerators;

use Karma\Generator\ConfigurationFileGenerator;
use Gaufrette\Filesystem;
use Karma\Configuration;
use Karma\Generator\VariableProvider;
use Symfony\Component\Yaml\Yaml;
use Karma\Application;

class YamlGenerator extends AbstractFileGenerator implements ConfigurationFileGenerator
{
    private
        $files;

    public function __construct(Filesystem $fs, Configuration $reader, VariableProvider $variableProvider)
    {
        parent::__construct($fs, $reader, $variableProvider);

        $this->files = array();
    }


    protected function generateVariable($variableName, $value)
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

    protected function postGenerate()
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

    private function backupFile($filename)
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

    private function computeFilename($file)
    {
        return "$file.yml";
    }

    private function formatContent($content)
    {
        return Yaml::dump($content);
    }
}