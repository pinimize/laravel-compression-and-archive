<?php

declare(strict_types=1);

namespace Pinimize\Contracts;

use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

interface CompressionContract
{
    /**
     * Compress data and return the compress data as a string.
     *
     * @param  StreamInterface|File|UploadedFile|string|resource  $contents
     * @param  string|array<string, scalar|null>  $options
     */
    public function string($contents, string|array $options = []): string;

    /**
     * Get a PHP resource of the compressed data.
     *
     * @param  StreamInterface|File|UploadedFile|string|resource  $contents
     * @param  string|array<string, scalar|null>  $options
     * @return resource
     */
    public function resource($contents, string|array $options = []);

    /**
     * Write compressed contents to a file specified by the path.
     *
     * @param  StreamInterface|File|UploadedFile|string|resource  $contents
     * @param  string|array<string, scalar|null>  $options
     */
    public function put(string $path, $contents, string|array $options = []): bool;

    /**
     * Compress and create a streamed download response for a given file.
     *
     * @param  array<string, scalar>  $headers
     * @param  string|array<string, scalar|null>  $options
     */
    public function download(string $path, ?string $name = null, array $headers = [], string|array $options = []): StreamedResponse;

    /**
     * Get the compression ratio between original and compressed data.
     *
     * @param  string  $original  Path to the original file
     * @param  string  $compressed  Path to the compressed file
     * @param  string|array<string, scalar|null>  $options
     * @return float The compression ratio
     */
    public function getRatio(string $original, string $compressed, string|array $options = []): float;

    /**
     * Get the list of supported compression algorithms.
     *
     * @return array<int, scalar>
     */
    public function getSupportedAlgorithms(): array;
}
