<?php

declare(strict_types = 1);

namespace Karma\Configuration;

interface FileParser
{
    public function getVariables(): array;
}
