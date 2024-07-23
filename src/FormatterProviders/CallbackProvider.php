<?php

declare(strict_types = 1);

namespace Karma\FormatterProviders;

use Karma\Formatter;
use Karma\FormatterProvider;

final class CallbackProvider implements FormatterProvider
{
    private \Closure
        $closure;

    public function __construct(\Closure $closure)
    {
        $this->closure = $closure;
    }

    public function hasFormatter(?string $index): true
    {
        return true;
    }

    public function formatter(?string $fileExtension, ?string $index = null): Formatter
    {
        $closure = $this->closure;

        return $closure($fileExtension, $index);
    }
}
