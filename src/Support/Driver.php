<?php

declare(strict_types=1);

namespace Pinimize\Support;

abstract class Driver
{
    /**
     * @var array<string, scalar|null>
     */
    protected array $config;

    abstract public function getDefaultEncoding(): int;

    /**
     * @return array<string, scalar|null>
     */
    public function getConfig(): array
    {
        return $this->config + [
            'level' => -1,
            'encoding' => $this->getDefaultEncoding(),
            'disk' => null,
        ];
    }

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
