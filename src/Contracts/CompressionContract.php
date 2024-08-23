<?php

declare(strict_types=1);

namespace Pinimize\Contracts;

interface CompressionContract
{
    /**
     * Compress a string.
     *
     * @param  string  $string  The string to compress
     * @param  array  $options  Additional options for compression
     * @return string The compressed string
     */
    public function string(string $string, array $options = []): string;
}
