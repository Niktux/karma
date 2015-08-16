<?php

namespace Karma\Display;

use SebastianBergmann\Diff\Differ;

class Diff
{
    private
        $differ;

    public function __construct()
    {
        $this->differ = new Differ();
    }

    public function diff($sourceFileContent, $targetFileContent)
    {
        $patch = $this->differ->diff($sourceFileContent, $targetFileContent);

        if(! empty($patch))
        {
            $patch = $this->filter($patch);
        }

        return $patch;
    }

    private function filter($patch)
    {
        $lines = explode(PHP_EOL, $patch);
        $lines = array_slice($lines, 2);

        $filteredLines = array();

        $lines = $this->computeDistance($lines);

        foreach($lines as $line)
        {
            $leadingChar = substr($line, 0 , 1);

            if($leadingChar === '+')
            {
                $line = "<addition>$line</addition>";
            }
            elseif($leadingChar === '-')
            {
                $line = "<deletion>$line</deletion>";
            }

            $filteredLines[] = $line;
        }

        return implode(PHP_EOL, $filteredLines);
    }

    private function computeDistance(array $lines)
    {
        // TODO
        return $lines;
    }
}
