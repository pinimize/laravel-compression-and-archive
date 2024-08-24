<?php

declare(strict_types=1);

namespace Pinimize\Compression;

use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Pinimize\Contracts\CompressionContract;
use Pinimize\Support\Driver;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

abstract class AbstractCompressionDriver extends Driver implements CompressionContract
{
    public function __construct(protected array $config) {}

    abstract public function getDefaultEncoding(): int;

    public function getConfig(): array
    {
        return $this->config + [
            'level' => -1,
            'encoding' => $this->getDefaultEncoding(),
            'disk' => null,
        ];
    }

    public function string(string $string, array $options = []): string
    {
        $options = $this->mergeWithConfig($options);

        return $this->compressString($string, $options['level'], $options['encoding']);
    }

    abstract protected function compressString(string $string, int $level, int $encoding): string;

    public function resource($resource, array $options = [])
    {
        if (! is_resource($resource)) {
            throw new RuntimeException('Invalid resource provided');
        }

        $options = $this->mergeWithConfig($options);
        $outStream = $this->createOutputStream();
        $this->compressStream($resource, $outStream, $options);

        rewind($outStream);

        return $outStream;
    }

    public function file(string $from, ?string $to = null, array $options = []): bool
    {
        if (is_string($options['disk'] ?? null) && ! Storage::disk($options['disk'])->exists($from)) {
            throw new RuntimeException("File does not exist: {$from}");
        }

        if (($options['disk'] ?? null) === null && ! file_exists($from)) {
            throw new RuntimeException("Source file does not exist: {$from}");
        }

        if ($to === null) {
            $to = Str::finish($from, '.'.$this->getFileExtension());
        }

        $options = $this->mergeWithConfig($options);
        $sourceHandle = $this->openSourceFile($from, $options);
        if (is_string($to) && is_string($options['disk'] ?? null)) {
            Storage::disk($options['disk'])->put($to, $this->resource($sourceHandle));
        }
        $outStream = $this->createOutputStream($to, $options);

        $this->compressStream($sourceHandle, $outStream, $options);

        fclose($sourceHandle);
        fclose($outStream);

        return true;
    }

    public function getRatio(string $original, string $compressed, array $options = []): float
    {
        $originalSize = strlen($original);
        $compressedSize = strlen($compressed);

        if ($originalSize === 0) {
            return 0.0;
        }

        return 1 - ($compressedSize / $originalSize);
    }

    /**
     * Write compressed contents to a file specified by the path.
     *
     * @param  StreamInterface|File|UploadedFile|string|resource  $contents
     */
    public function put(string $path, $contents, array $options = []): bool
    {
        $options = $this->mergeWithConfig($options);

        if (is_string($contents)) {
            return $this->putString($path, $contents, $options);
        }

        if ($contents instanceof StreamInterface) {
            return $this->putStream($path, $contents, $options);
        }

        if ($contents instanceof File || $contents instanceof UploadedFile) {
            return $this->putFile($path, $contents, $options);
        }

        if (is_resource($contents)) {
            return $this->putResource($path, $contents, $options);
        }

        throw new RuntimeException('Unsupported content type');
    }

    /**
     * Write a compressed string to a file on disk.
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
     * Write a compressed stream to a file.
     */
    protected function putStream(string $path, StreamInterface $stream, array $options): bool
    {
        $resource = $stream->detach();
        if (! is_resource($resource)) {
            throw new RuntimeException('Could not detach stream');
        }

        return $this->putResource($path, $resource, $options);
    }

    /**
     * Write a compressed file to storage.
     *
     * @param  File|UploadedFile  $file
     */
    protected function putFile(string $path, $file, array $options): bool
    {
        $resource = fopen($file->getRealPath(), 'r');
        if ($resource === false) {
            throw new RuntimeException('Could not open file');
        }

        return $this->putResource($path, $resource, $options);
    }

    /**
     * Write a compressed resource to a file.
     *
     * @param  resource  $resource
     */
    protected function putResource(string $path, $resource, array $options): bool
    {
        $compressedResource = $this->resource($resource, $options);
        $success = false;

        if (is_string($options['disk'] ?? null)) {
            return Storage::disk($options['disk'])->writeStream($path, $compressedResource);
        }

        $outputResource = fopen($path, 'w');
        if ($outputResource !== false) {
            stream_copy_to_stream($compressedResource, $outputResource);
            fclose($outputResource);
            $success = true;
        }

        fclose($compressedResource);
        fclose($resource);

        return $success;
    }

    /**
     * Create a response that forces the user's browser to download the compressed file.
     *
     * @param  array  $options  Compression options
     */
    public function download(string $path, ?string $name = null, array $headers = [], array $options = []): StreamedResponse
    {
        if (is_string($options['disk'] ?? null) && ! Storage::disk($options['disk'])->exists($path)) {
            throw new RuntimeException("File does not exist: {$path}");
        }

        if (($options['disk'] ?? null) === null && ! file_exists($path)) {
            throw new RuntimeException("File does not exist: {$path}");
        }

        $name ??= basename($path).'.'.$this->getFileExtension();
        $disposition = 'attachment; filename="'.addcslashes($name, '"').'"';

        $headers = array_merge([
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => $disposition,
        ], $headers);

        return new StreamedResponse(function () use ($path, $options): void {
            $sourceStream = $this->openSourceFile($path, $options);
            $compressedStream = $this->resource($sourceStream, $options);
            fpassthru($compressedStream);
            fclose($sourceStream);
            fclose($compressedStream);
        }, 200, $headers);
    }

    abstract public function getSupportedAlgorithms(): array;

    abstract public function getFileExtension(): string;

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

    protected function compressStream($input, $output, array $options)
    {
        $level = $options['level'] ?? -1;
        $encoding = $options['encoding'] ?? $this->getDefaultEncoding();

        $deflateContext = deflate_init($encoding, ['level' => $level]);
        if ($deflateContext === false) {
            throw new RuntimeException('Failed to initialize deflate context');
        }

        while (! feof($input)) {
            $chunk = fread($input, 8192);
            if ($chunk === false) {
                throw new RuntimeException('Failed to read from input stream');
            }

            $compressed = deflate_add($deflateContext, $chunk, ZLIB_NO_FLUSH);
            fwrite($output, $compressed);
        }

        $compressed = deflate_add($deflateContext, '', ZLIB_FINISH);
        fwrite($output, $compressed);
    }
}
