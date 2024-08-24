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
     * Compress a file.
     *
     * @param  array  $options  Additional options for compression
     * @return bool Boolean indicating success
     */
    public function file(string $from, ?string $to = null, array $options = []): bool;

    /**
     * Compress a resource.
     *
     * @param  resource  $resource  The resource to compress
     * @param  string|null  $destination  Path to the destination file (optional)
     * @param  array  $options  Additional options for compression
     * @return resource|bool The compressed resource or boolean indicating success
     */
    public function resource($resource, array $options = []);

    public function download(string $path, ?string $name = null, array $headers = [], array $options = []): StreamedResponse;

    /**
     * Get the compression ratio between original and compressed data.
     *
     * @param  string  $original  Path to the original file
     * @param  string  $compressed  Path to the compressed file
     * @return float The compression ratio
     */
    public function getRatio(string $original, string $compressed, array $options = []): float;

    /**
     * Get the list of supported compression algorithms.
     *
     * @return array<int, scalar>
     */
    public function getSupportedAlgorithms(): array;
}
