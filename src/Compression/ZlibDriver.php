<?php

declare(strict_types=1);

namespace Pinimize\Compression;

use Pinimize\Contracts\CompressionContract;
use Pinimize\Support\Driver;

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

        return zlib_encode($string, ZLIB_ENCODING_DEFLATE, $options['level']);
    }
}
