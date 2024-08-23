<?php

declare(strict_types=1);

namespace Pinimize\Compression;

use Pinimize\Contracts\CompressionContract;
use Pinimize\Support\Driver;
use RuntimeException;

/**
 * @phpstan-type GzipConfigArray array{
 *     level?: int,
 *     encoding?: int,
 * }
 */
class GzipDriver extends Driver implements CompressionContract
{
    /**
     * @param  GzipConfigArray  $config
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
            'encoding' => FORCE_GZIP,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function string(string $string, array $options = []): string
    {
        $options = $this->mergeWithConfig($options);

        return gzencode($string, $options['level'], $options['encoding']);
    }

    public function resource($resource, array $options = [])
    {
        if (! is_resource($resource)) {
            throw new RuntimeException('Invalid resource provided');
        }

        $options = $this->mergeWithConfig($options);
        $level = $options['level'];
        $encoding = $options['encoding'];

        $outStream = fopen('php://temp', 'w+b');
        if ($outStream === false) {
            throw new RuntimeException('Failed to open output stream');
        }

        $deflateContext = deflate_init(ZLIB_ENCODING_GZIP, ['level' => $level]);
        if ($deflateContext === false) {
            throw new RuntimeException('Failed to initialize deflate context');
        }

        while (! feof($resource)) {
            $chunk = fread($resource, 8192);
            if ($chunk === false) {
                throw new RuntimeException('Failed to read from resource');
            }

            $compressed = deflate_add($deflateContext, $chunk, ZLIB_NO_FLUSH);
            fwrite($outStream, $compressed);
        }

        $compressed = deflate_add($deflateContext, '', ZLIB_FINISH);
        fwrite($outStream, $compressed);

        rewind($outStream);

        return $outStream;
    }

    public function getFileExtension(): string
    {
        return 'gz';
    }
}
