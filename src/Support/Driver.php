<?php

declare(strict_types=1);

namespace Pinimize\Support;

abstract class Driver
{
    abstract public function getConfig(): array;

    /**
     * @param  string|array<string, scalar|null>  $options
     * @return array<string, scalar|null>
     */
    public function parseOptions(string|array $options): array
    {
        if (is_string($options)) {
            $options = ['disk' => $options];
        }

        return $options + $this->getConfig();
    }
}
