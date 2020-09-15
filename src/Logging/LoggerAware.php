<?php

declare(strict_types = 1);

namespace Karma\Logging;

use Psr\Log\LoggerInterface;

trait LoggerAware
{
    private LoggerInterface
        $logger;

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    private function error($message): void
    {
        $this->logger->error($message);
    }

    private function warning($message): void
    {
        $this->logger->warning($message);
    }

    private function info($message): void
    {
        $this->logger->info($message);
    }

    private function debug($message): void
    {
        $this->logger->debug($message);
    }
}
