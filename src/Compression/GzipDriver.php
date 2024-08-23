<?php

declare(strict_types=1);

namespace Pinimize\Compression;

use Pinimize\Contracts\CompressionContract;
use Pinimize\Support\Driver;

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

    public function getFileExtension(): string
    {
        return 'gz';
    }
}
