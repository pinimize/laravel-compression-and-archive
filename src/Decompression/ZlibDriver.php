<?php

declare(strict_types=1);

namespace Pinimize\Decompression;

use ErrorException;
use Pinimize\Exceptions\InvalidCompressedDataException;
use RuntimeException;

/**
 * @phpstan-type ZlibConfigArray  array{
 *     level: int,
 *     encoding: int,
 *     disk: string|null,
 *     max_length: int|null
 * }
 */
class ZlibDriver extends AbstractDecompressionDriver
{
    public function getDefaultEncoding(): int
    {
        return ZLIB_ENCODING_DEFLATE;
    }

    /**
     * @param  array<string, scalar|null>  $options
     *
     * @throws InvalidCompressedDataException
     */
    protected function decompressString(string $string, array $options): string
    {
        try {
            return zlib_decode($string, $options['max_length'] ?? 0);
        } catch (ErrorException) {
            throw new InvalidCompressedDataException('This is not valid zlib data');
        }
    }

    /**
     * @param  array<string, scalar|null>  $options
     *
     * @throws RuntimeException
     */
    protected function decompressStream($input, $output, array $options): void
    {
        $encoding = $options['encoding'] ?? $this->getDefaultEncoding();
        $inflateContext = inflate_init($encoding);
        if ($inflateContext === false) {
            throw new RuntimeException('Failed to initialize inflate context');
        }

        while (! feof($input)) {
            $chunk = fread($input, 8192);
            if ($chunk === false) {
                throw new RuntimeException('Failed to read from input stream');
            }

            $decompressed = inflate_add($inflateContext, $chunk);
            fwrite($output, $decompressed);
        }

        $decompressed = inflate_add($inflateContext, '', ZLIB_FINISH);
        fwrite($output, $decompressed);
    }
}
