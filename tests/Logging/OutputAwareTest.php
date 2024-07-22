<?php

declare(strict_types = 1);

namespace Karma\Logging;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Xpmock\TestCaseTrait;

final class Output
{
    use OutputAware;
}

class OutputAwareTest extends TestCase
{
    use TestCaseTrait;

    private BufferedOutput
        $buffer;
    private Output
        $output;

    protected function setUp(): void
    {
        $this->output = new Output();

        $this->buffer = new BufferedOutput();
        $this->buffer->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
        $this->output->setOutput($this->buffer);
    }

    public function testError(): void
    {
        $this->error('foo', false);
        self::assertSame('<fg=red>foo</fg=red>', $this->buffer->fetch());

        $this->error('bar', true);
        self::assertSame("<fg=red>bar</fg=red>\n", $this->buffer->fetch());
    }

    public function testWarning(): void
    {
        $this->warning('foo', false);
        self::assertSame("<fg=yellow>foo</fg=yellow>", $this->buffer->fetch());

        $this->warning('bar', true);
        self::assertSame("<fg=yellow>bar</fg=yellow>\n", $this->buffer->fetch());
    }

    public function testInfo(): void
    {
        $this->info('foo', false);
        self::assertSame("<fg=white>foo</fg=white>", $this->buffer->fetch());

        $this->info('bar', true);
        self::assertSame("<fg=white>bar</fg=white>\n", $this->buffer->fetch());
    }

    public function testDebug(): void
    {
        $this->debug('foo', false);
        self::assertEmpty($this->buffer->fetch());

        $this->buffer->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
        $this->debug('foo', false);
        self::assertSame("<fg=white>foo</fg=white>", $this->buffer->fetch());

        $this->debug('bar', true);
        self::assertSame("<fg=white>bar</fg=white>\n", $this->buffer->fetch());
    }

    private function error(string $message, bool $newline)
    {
        return $this->reflect($this->output)->error($message, $newline, OutputInterface::OUTPUT_RAW);
    }

    private function warning(string $message, bool $newline)
    {
        return $this->reflect($this->output)->warning($message, $newline, OutputInterface::OUTPUT_RAW);
    }

    private function info($message, $newline)
    {
        return $this->reflect($this->output)->info($message, $newline, OutputInterface::OUTPUT_RAW);
    }

    private function debug($message, $newline)
    {
        return $this->reflect($this->output)->debug($message, $newline, OutputInterface::OUTPUT_RAW);
    }
}
