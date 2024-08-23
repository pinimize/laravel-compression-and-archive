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

    /**
     * Compress a resource.
     *
     * @param  resource  $resource  The resource to compress
     * @param  string|null  $destination  Path to the destination file (optional)
     * @param  array  $options  Additional options for compression
     * @return resource|bool The compressed resource or boolean indicating success
     */
    public function resource($resource, array $options = []);
}
