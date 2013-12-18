<?php

namespace Karma\Configuration\Parser;

use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;
use Monolog\Logger;

abstract class AbstractGroupParser implements GroupParser
{
    protected
        $logger,
        $currentFilePath;
    
    public function __construct()
    {
        $this->logger = new NullLogger();
        $this->currentFilePath = null;
    }
    
    public function setCurrentFile($filePath)
    {
        $this->currentFilePath = $filePath;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    
        return $this;
    }
    
    protected function log($message, $level = Logger::INFO)
    {
        return $this->logger->log($level, $message);
    }
    
    protected function error($message)
    {
        return $this->logger->log($message, Logger::ERROR);
    }
}