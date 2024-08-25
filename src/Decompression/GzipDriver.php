<?php

declare(strict_types=1);

namespace Pinimize\Decompression;

use ErrorException;
use Pinimize\Exceptions\InvalidCompressedDataException;
use RuntimeException;

class GzipDriver extends AbstractDecompressionDriver
{
    public function getDefaultEncoding(): int
    {
        return FORCE_GZIP;
    }

    /**
     * @param  array<string, scalar|null>  $options
     *
     * @throws InvalidCompressedDataException
     */
    protected function decompressString(string $string, array $options): string
    {
        try {
            return gzdecode($string, $options['max_length'] ?? 0);
        } catch (ErrorException) {
            throw new InvalidCompressedDataException('This is not valid gzip data');
        }
    }

    protected function decompressStream($input, $output, array $options): void
    {
        $inflateContext = inflate_init(ZLIB_ENCODING_GZIP);
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
