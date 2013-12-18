<?php

namespace Karma;

use Psr\Log\LoggerInterface;

trait LoggerAware
{
    private
        $logger;
    
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    
        return $this;
    }
    
    private function error($message)
    {
        return $this->logger->error($message);
    }
    
    private function warning($message)
    {
        return $this->logger->warning($message);
    }    
    
    private function info($message)
    {
        return $this->logger->info($message);
    }
    
    private function debug($message)
    {
        return $this->logger->debug($message);
    }
}