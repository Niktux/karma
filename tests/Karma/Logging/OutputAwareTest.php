<?php

declare(strict_types = 1);

namespace Karma\Logging;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class OutputAwareTest extends TestCase
{
    use \Xpmock\TestCaseTrait;

    private
        $buffer,
        $output;

    protected function setUp()
    {
        $this->output = $this->getObjectForTrait('\Karma\Logging\OutputAware');

        $this->buffer = new BufferedOutput();
        $this->buffer->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
        $this->output->setOutput($this->buffer);
    }

    public function testError()
    {
        $this->error('foo', false);
        $this->assertSame('<fg=red>foo</fg=red>', $this->buffer->fetch());

        $this->error('bar', true);
        $this->assertSame("<fg=red>bar</fg=red>\n", $this->buffer->fetch());
    }

    public function testWarning()
    {
        $this->warning('foo', false);
        $this->assertSame("<fg=yellow>foo</fg=yellow>", $this->buffer->fetch());

        $this->warning('bar', true);
        $this->assertSame("<fg=yellow>bar</fg=yellow>\n", $this->buffer->fetch());
    }

    public function testInfo()
    {
        $this->info('foo', false);
        $this->assertSame("<fg=white>foo</fg=white>", $this->buffer->fetch());

        $this->info('bar', true);
        $this->assertSame("<fg=white>bar</fg=white>\n", $this->buffer->fetch());
    }

    public function testDebug()
    {
        $this->debug('foo', false);
        $this->assertEmpty($this->buffer->fetch());

        $this->buffer->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
        $this->debug('foo', false);
        $this->assertSame("<fg=white>foo</fg=white>", $this->buffer->fetch());

        $this->debug('bar', true);
        $this->assertSame("<fg=white>bar</fg=white>\n", $this->buffer->fetch());
    }

    private function error($message, $newline)
    {
        return $this->reflect($this->output)->error($message, $newline, OutputInterface::OUTPUT_RAW);
    }

    private function warning($message, $newline)
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
