<?php

namespace Karma\Logging;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Psr\Log\LogLevel;

class OutputInterfaceAdapter implements LoggerInterface
{
    use \Psr\Log\LoggerTrait;

    private
        $output,
        $levelConversionTable;
    
    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
        
        $this->levelConversionTable = array(
            LogLevel::DEBUG => OutputInterface::VERBOSITY_DEBUG,
            LogLevel::INFO => OutputInterface::VERBOSITY_NORMAL,
            LogLevel::WARNING => OutputInterface::VERBOSITY_VERY_VERBOSE,
            LogLevel::ERROR => OutputInterface::VERBOSITY_NORMAL
        );
    }
    
    public function log($level, $message, array $context = array())
    {
        $this->output->writeln($message, $this->convertLevel($level));
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
}