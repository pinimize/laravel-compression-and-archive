<?php

declare(strict_types=1);

namespace Pinimize\Compression;

/**
 * @phpstan-type GzipConfigArray  array{
 *     level: int,
 *     encoding: int,
 *     disk: string|null,
 *     max_length: int|null
 * }
 */
class GzipDriver extends AbstractCompressionDriver
{
    public function getDefaultEncoding(): int
    {
        return FORCE_GZIP;
    }

    protected function compressString(string $string, int $level, int $encoding): string
    {
        return gzencode($string, $level, $encoding);
    }

    public function getSupportedAlgorithms(): array
    {
        return [FORCE_GZIP];
    }

    public function getFileExtension(): string
    {
        return 'gz';
    }
}
