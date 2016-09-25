<?php

namespace Karma\FormatterProviders;

use Karma\FormatterProvider;

class CallbackProvider implements FormatterProvider
{
    private
        $closure;

    public function __construct(\Closure $closure)
    {
        $this->closure = $closure;
    }

    public function hasFormatter($index)
    {
        return true;
    }

    public function getFormatter($fileExtension, $index = null)
    {
        $closure = $this->closure;

        return $closure($fileExtension, $index);
    }
}
