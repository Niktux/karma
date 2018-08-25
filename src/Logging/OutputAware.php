<?php

declare(strict_types = 1);

namespace Karma\Logging;

use Symfony\Component\Console\Output\OutputInterface;

trait OutputAware
{
    protected
        $output = null;

    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;

        return $this;
    }

    protected function error($messages, $newline = false, $type = OutputInterface::OUTPUT_NORMAL)
    {
        return $this->write($messages, $newline, $type, OutputInterface::VERBOSITY_NORMAL, 'red');
    }

    protected function warning($messages, $newline = false, $type = OutputInterface::OUTPUT_NORMAL)
    {
        return $this->write($messages, $newline, $type, OutputInterface::VERBOSITY_NORMAL, 'yellow');
    }

    protected function info($messages, $newline = false, $type = OutputInterface::OUTPUT_NORMAL)
    {
        return $this->write($messages, $newline, $type, OutputInterface::VERBOSITY_NORMAL, 'white');
    }

    protected function debug($messages, $newline = false, $type = OutputInterface::OUTPUT_NORMAL)
    {
        return $this->write($messages, $newline, $type, OutputInterface::VERBOSITY_VERBOSE, 'white');
    }

    private function write($messages, $newline, $type, $verbosity, $textColor)
    {
        if($this->output instanceof OutputInterface)
        {
            if($verbosity <= $this->output->getVerbosity())
            {
                if(! is_array($messages))
                {
                    $messages = array($messages);
                }

                array_walk($messages, function(& $message) use($textColor) {
                    $message = "<fg=$textColor>$message</fg=$textColor>";
                });

                $this->output->write($messages, $newline, $type);
            }
        }
    }
}
