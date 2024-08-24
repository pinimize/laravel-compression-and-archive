<?php

declare(strict_types=1);

namespace Pinimize\Compression;

use Illuminate\Support\Str;
use Pinimize\Contracts\CompressionContract;
use Pinimize\Support\Driver;
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
        if (! file_exists($from)) {
            throw new RuntimeException("Source file does not exist: {$from}");
        }

        if ($to === null) {
            $to = Str::finish($from, '.'.$this->getFileExtension());
        }

        $options = $this->mergeWithConfig($options);
        $sourceHandle = $this->openSourceFile($from);
        $outStream = $this->createOutputStream($to);

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
     * Create a response that forces the user's browser to download the compressed file.
     *
     * @param  array  $options  Compression options
     */
    public function download(string $path, ?string $name = null, array $headers = [], array $options = []): StreamedResponse
    {
        if (! file_exists($path)) {
            throw new RuntimeException("File does not exist: {$path}");
        }

        $name ??= basename($path).'.'.$this->getFileExtension();
        $disposition = 'attachment; filename="'.addcslashes($name, '"').'"';

        $headers = array_merge([
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => $disposition,
        ], $headers);

        return new StreamedResponse(function () use ($path, $options): void {
            $sourceStream = $this->openSourceFile($path);
            $compressedStream = $this->resource($sourceStream, $options);
            fpassthru($compressedStream);
            fclose($sourceStream);
            fclose($compressedStream);
        }, 200, $headers);
    }

    abstract public function getSupportedAlgorithms(): array;

    abstract public function getFileExtension(): string;

    protected function openSourceFile(string $source)
    {
        $sourceHandle = fopen($source, 'rb');
        if ($sourceHandle === false) {
            throw new RuntimeException("Failed to open source file: {$source}");
        }

        return $sourceHandle;
    }

    protected function createOutputStream(?string $destination = null)
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
