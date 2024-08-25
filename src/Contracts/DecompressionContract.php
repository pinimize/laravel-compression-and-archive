<?php

declare(strict_types=1);

namespace Pinimize\Contracts;

use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

interface DecompressionContract
{
    /**
     * Decompress data and return the data as a string.
     *
     * @param  StreamInterface|File|UploadedFile|string|resource  $contents
     * @param  string|array<string, scalar|null>  $options
     */
    public function string($contents, string|array $options = []): string;

    /**
     * Decompress a resource, and return the data as a PHP resource.
     *
     * @param  StreamInterface|File|UploadedFile|string|resource  $contents
     * @param  string|array<string, scalar|null>  $options
     * @return resource
     */
    public function resource($contents, string|array $options = []);

    /**
     * Write decompressed contents to a file specified by the path.
     *
     * @param  StreamInterface|File|UploadedFile|string|resource  $contents
     * @param  string|array<string, scalar|null>  $options
     */
    public function put(string $path, $contents, string|array $options = []): bool;

    /**
     * Decompress and create a streamed download response for a given file.
     *
     * @param  array<string, scalar>  $headers
     * @param  string|array<string, scalar|null>  $options
     */
    public function download(string $path, ?string $name = null, array $headers = [], string|array $options = []): StreamedResponse;
}
