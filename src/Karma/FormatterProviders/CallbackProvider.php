<?php

declare(strict_types = 1);

namespace Karma\FormatterProviders;

use Karma\Formatter;
use Karma\FormatterProvider;

class CallbackProvider implements FormatterProvider
{
    private
        $closure;

    public function __construct(\Closure $closure)
    {
        $this->closure = $closure;
    }

    public function hasFormatter(?string $index): bool
    {
        return true;
    }

    public function getFormatter(?string $fileExtension, ?string $index = null): Formatter
    {
        $closure = $this->closure;

        return $closure($fileExtension, $index);
    }
}
