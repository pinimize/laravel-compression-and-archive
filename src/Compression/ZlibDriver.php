<?php

declare(strict_types=1);

namespace Pinimize\Compression;

/**
 * @phpstan-type ZlibConfigArray  array{
 *     level: int,
 *     encoding: int,
 *     disk: string|null,
 *     max_length: int|null
 * }
 */
class ZlibDriver extends AbstractCompressionDriver
{
    public function getDefaultEncoding(): int
    {
        return ZLIB_ENCODING_DEFLATE;
    }

    protected function compressString(string $string, int $level, int $encoding): string
    {
        return zlib_encode($string, $encoding, $level);
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
}
