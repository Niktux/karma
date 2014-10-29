<?php

namespace Karma\Generator\NameTranslators;

use Karma\Generator\NameTranslator;
use Karma\Application;
use Karma\Generator\ConfigurationFileGenerator;

class FilePrefixTranslator implements NameTranslator
{
    private
        $masterFilename,
        $prefixForMasterFile;

    public function __construct()
    {
        $this->masterFilename = Application::DEFAULT_MASTER_FILE;
        $this->prefixForMasterFile = false;
    }

    public function translate($file, $variable)
    {
        $prefix = $this->computePrefix($file);

        if($prefix !== false)
        {
            $variable = $prefix . ConfigurationFileGenerator::DELIMITER . $variable;
        }

        return $variable;
    }

    private function computePrefix($file)
    {
        $prefix = $this->prefixForMasterFile;

        if($file !== $this->masterFilename)
        {
            $prefix = pathinfo($file, PATHINFO_FILENAME);
        }

        return $prefix;
    }

    public function changeMasterFile($masterFilename)
    {
        $this->masterFilename = $masterFilename;

        return $this;
    }

    public function setPrefixForMasterFile($prefix)
    {
        $this->prefixForMasterFile = $prefix;

        return $this;
    }
}