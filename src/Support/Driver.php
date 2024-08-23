<?php

declare(strict_types=1);

namespace Pinimize\Support;

abstract class Driver
{
    abstract public function getConfig(): array;

    public function mergeWithConfig(array $options): array
    {
        return $options + $this->getConfig();
    }
}
