<?php

declare(strict_types=1);

namespace Pinimize\Compression;

use GuzzleHttp\Psr7\StreamWrapper;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Pinimize\Contracts\CompressionContract;
use Pinimize\Support\Driver;
use Pinimize\Support\ResourceHelpersTrait;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

abstract class AbstractCompressionDriver extends Driver implements CompressionContract
{
    use ResourceHelpersTrait;

    /**
     * @param  array<string, scalar|null>  $config
     */
    public function __construct(protected array $config) {}

    /**
     * {@inheritDoc}
     */
    public function string($contents, string|array $options = []): string
    {
        $options = $this->parseOptions($options);

        if (is_string($contents)) {
            return $this->compressString($contents, (int) $options['level'], $options['encoding']);
        }
        if ($contents instanceof File || $contents instanceof UploadedFile) {
            $contents = fopen($contents->getRealPath(), 'r');
        }
        if ($contents instanceof StreamInterface) {
            $contents = StreamWrapper::getResource($contents);
        }
        if (is_resource($contents)) {
            return stream_get_contents($this->resource($contents, $options));
        }

        throw new RuntimeException('Invalid contents provided');
    }

    /**
     * {@inheritDoc}
     */
    public function resource($contents, string|array $options = [])
    {
        $options = $this->parseOptions($options);
        $disk = $options['disk'] ?? null;

        if ($contents instanceof File || $contents instanceof UploadedFile) {
            $contents = fopen($contents->getRealPath(), 'r');
        }
        if (is_string($contents)) {
            $isFilepath = is_string($disk) ? Storage::disk($disk)->exists($contents) : file_exists($contents);
            if ($isFilepath) {
                $contents = $disk === null ? fopen($contents, 'r') : Storage::disk($disk)->readStream($contents);
            } else {
                $resource = fopen('php://memory', 'r+');
                fwrite($resource, $contents);
                rewind($resource);
                $contents = $resource;
            }
        }
        if ($contents instanceof StreamInterface) {
            $contents = StreamWrapper::getResource($contents);
        }
        if (! is_resource($contents)) {
            throw new RuntimeException('Invalid resource provided');
        }
        $outStream = $this->createOutputStream();
        $this->compressStream($contents, $outStream, $options);

        rewind($outStream);

        return $outStream;
    }

    /**
     * {@inheritDoc}
     */
    public function put(string $path, $contents, string|array $options = []): bool
    {
        $options = $this->parseOptions($options);
        $disk = $options['disk'] ?? null;

        if (is_string($contents)) {
            $isFilepath = is_string($disk) ? Storage::disk($disk)->exists($contents) : file_exists($contents);
            if ($isFilepath === false) {
                return $this->putString($path, $contents, $options);
            }

            $contents = $disk === null ? fopen($contents, 'r') : Storage::disk($disk)->readStream($contents);
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
     * {@inheritDoc}
     */
    public function download(string $path, ?string $name = null, array $headers = [], string|array $options = []): StreamedResponse
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

    /**
     * {@inheritDoc}
     */
    public function getRatio(string $original, string $compressed, string|array $options = []): float
    {
        $originalSize = strlen($original);
        $compressedSize = strlen($compressed);

        if ($originalSize === 0) {
            return 0.0;
        }

        return 1 - ($compressedSize / $originalSize);
    }

    abstract protected function compressString(string $string, int $level, int $encoding): string;

    abstract public function getSupportedAlgorithms(): array;

    abstract public function getFileExtension(): string;

    /**
     * @param  resource  $input
     * @param  resource  $output
     * @param  array<string, scalar|null>  $options
     */
    protected function compressStream($input, $output, array $options): void
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
