<?php

declare(strict_types = 1);

namespace Karma\Logging;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;
use Psr\Log\LogLevel;

class OutputInterfaceAdapter implements LoggerInterface
{
    use \Psr\Log\LoggerTrait;

    private OutputInterface
        $output;
    private array
        $levelConversionTable;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;

        $this->levelConversionTable = [
            LogLevel::DEBUG => OutputInterface::VERBOSITY_VERBOSE,
            LogLevel::INFO => OutputInterface::VERBOSITY_NORMAL,
            LogLevel::WARNING => OutputInterface::VERBOSITY_NORMAL,
            LogLevel::ERROR => OutputInterface::VERBOSITY_NORMAL
        ];
    }

    public function log($level, $message, array $context = [])
    {
        if($this->convertLevel($level) <= $this->output->getVerbosity())
        {
            $this->writeLevel($level);
            $this->output->writeln($message, OutputInterface::OUTPUT_NORMAL);
        }
    }

    private function convertLevel($level)
    {
        $verbosity = OutputInterface::VERBOSITY_NORMAL;

        if(array_key_exists($level, $this->levelConversionTable))
        {
            $verbosity = $this->levelConversionTable[$level];
        }

        return $verbosity;
    }

    private function writeLevel($level)
    {
        $message = str_pad(sprintf(
           '[%s]',
           strtoupper($level)
        ), 10);

        $this->output->write($this->colorizeMessage($level, $message));
    }

    private function colorizeMessage($level, $message)
    {
        $colors = [
            LogLevel::ERROR => 'red',
            LogLevel::WARNING => 'yellow',
        ];

        if(isset($colors[$level]))
        {
            $message = sprintf(
               '<%1$s>%2$s</%1$s>',
                'fg=' . $colors[$level],
                $message
            );
        }

        return $message;
    }
}
