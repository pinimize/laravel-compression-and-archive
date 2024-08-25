<?php

declare(strict_types=1);

namespace Pinimize\Tests\Decompression;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Pinimize\Decompression\ZlibDriver;
use Pinimize\Exceptions\InvalidCompressedDataException;
use Pinimize\Tests\TestCase;

class ZlibDriverTest extends TestCase
{
    public $zlibDriver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->zlibDriver = new ZlibDriver([]);
    }

    #[Test]
    #[DataProvider('compressionMethodsProvider')]
    public function it_can_decompress_a_string(callable $compressionFunction, int $encoding): void
    {
        $original = 'Hello, World!';
        $compressed = $compressionFunction($original, $encoding);
        $decompressed = $this->zlibDriver->string($compressed);

        $this->assertEquals($original, $decompressed);
    }

    public static function compressionMethodsProvider(): array
    {
        return [
            'ZLIB_ENCODING_DEFLATE' => [
                'compressionFunction' => 'zlib_encode',
                'encoding' => ZLIB_ENCODING_DEFLATE,
            ],
            'ZLIB_ENCODING_RAW' => [
                'compressionFunction' => 'zlib_encode',
                'encoding' => ZLIB_ENCODING_RAW,
            ],
            'ZLIB_ENCODING_GZIP' => [
                'compressionFunction' => 'zlib_encode',
                'encoding' => ZLIB_ENCODING_GZIP,
            ],
            'gzencode' => [
                'compressionFunction' => fn ($data): string|false => gzencode((string) $data),
                'encoding' => 0, // Not used for gzencode
            ],
        ];
    }

    #[Test]
    public function it_can_decompress_large_content(): void
    {
        $largeContent = str_repeat('Lorem ipsum dolor sit amet. ', 10000);
        $compressed = zlib_encode($largeContent, ZLIB_ENCODING_DEFLATE);
        $decompressed = $this->zlibDriver->string($compressed);
        $this->assertEquals($largeContent, $decompressed);
    }

    #[Test]
    public function it_can_handle_empty_string(): void
    {
        $emptyString = '';
        $compressed = zlib_encode($emptyString, ZLIB_ENCODING_DEFLATE);

        $decompressed = $this->zlibDriver->string($compressed);
        $this->assertEquals($emptyString, $decompressed);
    }

    #[Test]
    public function it_can_handle_invalid_compressed_data(): void
    {
        $this->expectException(InvalidCompressedDataException::class);
        $this->expectExceptionMessage('This is not valid zlib data');

        $invalidCompressedData = 'This is not valid gzip data';

        $this->zlibDriver->string($invalidCompressedData);
    }
}
