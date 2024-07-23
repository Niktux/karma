<?php

declare(strict_types = 1);

namespace Karma\Generator\NameTranslators;

use Karma\Generator\NameTranslator;
use Karma\Application;
use Karma\Generator\ConfigurationFileGenerator;

final class FilePrefixTranslator implements NameTranslator
{
    private string
        $masterFilename;
    private ?string
        $prefixForMasterFile;

    public function __construct()
    {
        $this->masterFilename = Application::DEFAULT_MASTER_FILE;
        $this->prefixForMasterFile = null;
    }

    public function translate(string $file, string $variable): string
    {
        $prefix = $this->computePrefix($file);

        if($prefix !== null)
        {
            $variable = $prefix . ConfigurationFileGenerator::DELIMITER . $variable;
        }

        return $variable;
    }

    private function computePrefix($file): ?string
    {
        $prefix = $this->prefixForMasterFile;

        if($file !== $this->masterFilename)
        {
            $prefix = pathinfo($file, PATHINFO_FILENAME);
        }

        return $prefix;
    }

    public function changeMasterFile(string $masterFilename): void
    {
        $this->masterFilename = $masterFilename;
    }

    public function setPrefixForMasterFile(string $prefix): void
    {
        $this->prefixForMasterFile = $prefix;
    }
}
