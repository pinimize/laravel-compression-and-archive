<?php

declare(strict_types=1);

namespace Pinimize\Compression;

use Illuminate\Support\Str;
use Pinimize\Contracts\CompressionContract;
use Pinimize\Support\Driver;
use RuntimeException;
use Throwable;

/**
 * @phpstan-type ZlibConfigArray array{
 *     level?: int,
 *     encoding?: int,
 * }
 */
class ZlibDriver extends Driver implements CompressionContract
{
    /**
     * @param  ZlibConfigArray  $config
     */
    public function __construct(
        protected array $config,
    ) {}

    /**
     * @return array{
     *     level: int,
     *     encoding: int,
     * }
     */
    public function getConfig(): array
    {
        return $this->config + [
            'level' => -1,
            'encoding' => ZLIB_ENCODING_DEFLATE,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function string(string $string, array $options = []): string
    {
        $options = $this->mergeWithConfig($options);
        $level = $options['level'] ?? -1;
        $encoding = $options['encoding'] ?? ZLIB_ENCODING_DEFLATE;

        return zlib_encode($string, $encoding, $level);
    }

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

    /**
     * Compress a file using zlib.
     *
     * @param  string  $from  Path to the source file
     * @param  string|null  $to  Path to the destination file (optional)
     * @param  array  $options  Additional options for compression
     * @return bool Indicates success
     */
    public function file(string $from, ?string $to = null, array $options = []): bool
    {
        if (! file_exists($from)) {
            throw new RuntimeException("Source file does not exist: $from");
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

    public function getSupportedAlgorithms(): array
    {
        return [
            ZLIB_ENCODING_RAW,
            ZLIB_ENCODING_GZIP,
            ZLIB_ENCODING_DEFLATE,
        ];
    }

    public function getFileExtension(): string
    {
        return 'zz';
    }

    /**
     * Open the source file.
     *
     * @return resource
     */
    protected function openSourceFile(string $source)
    {
        $sourceHandle = fopen($source, 'rb');
        if ($sourceHandle === false) {
            throw new RuntimeException("Failed to open source file: $source");
        }

        return $sourceHandle;
    }

    /**
     * Create an output stream.
     *
     * @return resource
     */
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
        } catch (Throwable $e) {
            throw new RuntimeException('Failed to open output stream: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Compress a stream.
     *
     * @param  resource  $input
     * @param  resource  $output
     */
    protected function compressStream($input, $output, array $options)
    {
        $level = $options['level'] ?? -1;
        $encoding = $options['encoding'] ?? ZLIB_ENCODING_DEFLATE;

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
