<?php

declare(strict_types=1);

namespace Pinimize\Support;

use GuzzleHttp\Psr7\StreamWrapper;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Throwable;

trait ResourceHelpersTrait
{
    /**
     * Write a compressed string to a file on disk.
     *
     * @param  array<string, scalar|null>  $options
     */
    protected function putString(string $path, string $contents, array $options): bool
    {
        $compressed = $this->string($contents, $options);
        if (! is_string($options['disk'] ?? null)) {
            return file_put_contents($path, $compressed) !== false;
        }

        return Storage::disk($options['disk'])->put($path, $compressed);
    }

    /**
     * @param  array<string, scalar|null>  $options
     */
    protected function putFile(string $path, File|UploadedFile $file, array $options): bool
    {
        $resource = fopen($file->getRealPath(), 'r');
        if ($resource === false) {
            throw new RuntimeException('Could not open file');
        }

        return $this->putResource($path, $resource, $options);
    }

    /**
     * @param  array<string, scalar|null>  $options
     */
    protected function putStream(string $path, StreamInterface $stream, array $options): bool
    {
        $resource = StreamWrapper::getResource($stream);
        if (! is_resource($resource)) {
            throw new RuntimeException('Could load the stream');
        }

        return $this->putResource($path, $resource, $options);
    }

    /**
     * @param  resource  $resource
     * @param  array<string, scalar|null>  $options
     */
    protected function putResource(string $path, $resource, array $options): bool
    {
        $processedResource = $this->resource($resource, $options);
        $success = false;

        if (is_string($options['disk'] ?? null)) {
            return Storage::disk($options['disk'])->writeStream($path, $processedResource);
        }

        $outputResource = fopen($path, 'w');
        if ($outputResource !== false) {
            stream_copy_to_stream($processedResource, $outputResource);
            fclose($outputResource);
            $success = true;
        }

        fclose($processedResource);
        fclose($resource);

        return $success;
    }

    /**
     * @param  array<string, scalar|null>  $options
     * @return resource
     */
    protected function openSourceFile(string $source, array $options = [])
    {
        if (is_string($options['disk'] ?? null)) {
            return Storage::disk($options['disk'])->readStream($source);
        }

        $sourceHandle = fopen($source, 'rb');
        if ($sourceHandle === false) {
            throw new RuntimeException("Failed to open source file: {$source}");
        }

        return $sourceHandle;
    }

    /**
     * @param  array<string, scalar|null>  $options
     * @return resource
     *
     * @throws RuntimeException
     */
    protected function createOutputStream(?string $destination = null, array $options = [])
    {
        try {
            $outStream = ($destination === null)
                ? fopen('php://temp', 'w+b')
                : fopen($destination, 'wb');

            if ($outStream === false) {
                throw new RuntimeException('Failed to open output stream');
            }

            return $outStream;
        } catch (Throwable $throwable) {
            throw new RuntimeException('Failed to open output stream: '.$throwable->getMessage(), 0, $throwable);
        }
    }
}
