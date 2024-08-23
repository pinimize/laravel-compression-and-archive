<?php

declare(strict_types=1);

namespace Pinimize\Compression;

use Pinimize\Contracts\CompressionContract;
use Pinimize\Support\Driver;
use RuntimeException;

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
        $level = $options['level'] ?? -1;
        $encoding = $options['encoding'] ?? ZLIB_ENCODING_DEFLATE;

        $outStream = fopen('php://temp', 'w+b');
        if ($outStream === false) {
            throw new RuntimeException('Failed to open output stream');
        }

        switch ($encoding) {
            case ZLIB_ENCODING_RAW:
                $filter = 'zlib.deflate';
                break;
            case ZLIB_ENCODING_GZIP:
                $filter = 'zlib.deflate';
                fwrite($outStream, "\x1f\x8b\x08\x00".pack('V', time())."\x00\x03");
                break;
            case ZLIB_ENCODING_DEFLATE:
            default:
                $filter = 'zlib.deflate';
                fwrite($outStream, "\x78\x9c"); // zlib header
                break;
        }

        $params = ['level' => $level];
        $deflateFilter = stream_filter_append($outStream, $filter, STREAM_FILTER_WRITE, $params);

        $crc = 0;
        $size = 0;
        $adler32 = 1;
        while (! feof($resource)) {
            $chunk = fread($resource, 8192);
            if ($chunk === false) {
                throw new RuntimeException('Failed to read from resource');
            }
            $size += strlen($chunk);
            $crc = crc32($chunk) ^ (($crc >> 8) & 0xFFFFFF);
            $adler32 = $this->updateAdler32($adler32, $chunk);
            fwrite($outStream, $chunk);
        }

        stream_filter_remove($deflateFilter);

        if ($encoding === ZLIB_ENCODING_GZIP) {
            // Add GZIP footer
            fwrite($outStream, pack('V', $crc));
            fwrite($outStream, pack('V', $size));
        } elseif ($encoding === ZLIB_ENCODING_DEFLATE) {
            // Add Adler-32 checksum
            fwrite($outStream, pack('N', $adler32));
        }

        rewind($outStream);

        return $outStream;
    }

    private function updateAdler32($adler, $data)
    {
        $s1 = $adler & 0xFFFF;
        $s2 = ($adler >> 16) & 0xFFFF;

        for ($i = 0; $i < strlen($data); $i++) {
            $s1 = ($s1 + ord($data[$i])) % 65521;
            $s2 = ($s2 + $s1) % 65521;
        }

        return ($s2 << 16) + $s1;
    }

    public function getFileExtension(): string
    {
        return 'zz';
    }
}
