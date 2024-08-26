<?php

declare(strict_types=1);

namespace Pinimize\Tests\Mixins;

use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Pinimize\Facades\Compression;
use Pinimize\Facades\Decompression;
use Pinimize\Mixins\StringCompressionMixin;
use Pinimize\Tests\TestCase;

class StringCompressionMixinTest extends TestCase
{
    private StringCompressionMixin $stringCompressionMixin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stringCompressionMixin = new StringCompressionMixin;
    }

    #[Test]
    public function it_can_return_a_compress_closure(): void
    {
        $compress = $this->stringCompressionMixin->compress();
        $this->assertIsCallable($compress);
    }

    #[Test]
    public function it_can_return_a_decompress_closure(): void
    {
        $decompress = $this->stringCompressionMixin->decompress();
        $this->assertIsCallable($decompress);
    }

    #[Test]
    public function it_can_compress_a_string_using_facade(): void
    {
        $compress = $this->stringCompressionMixin->compress();
        $string = 'Test string';

        Compression::shouldReceive('driver')
            ->once()
            ->withArgs([null])
            ->andReturnSelf();
        Compression::shouldReceive('string')
            ->once()
            ->with($string)
            ->andReturn('compressed_string');

        $result = $compress($string);
        $this->assertEquals('compressed_string', $result);
    }

    #[Test]
    public function it_can_decompress_a_string_using_facade(): void
    {
        $decompress = $this->stringCompressionMixin->decompress();
        $compressedString = 'compressed_string';

        Decompression::shouldReceive('driver')
            ->once()
            ->withArgs([null])
            ->andReturnSelf();
        Decompression::shouldReceive('string')
            ->once()
            ->with($compressedString)
            ->andReturn('Test string');

        $result = $decompress($compressedString);
        $this->assertEquals('Test string', $result);
    }

    #[Test]
    public function it_can_compress_and_decompress_a_string(): void
    {
        $originalString = 'This is a test string for compression and decompression.';

        $compressedString = Str::compress($originalString);
        $this->assertNotEquals($originalString, $compressedString);

        $decompressedString = Str::decompress($compressedString);
        $this->assertEquals($originalString, $decompressedString);
    }

    #[Test]
    public function it_can_handle_empty_string(): void
    {
        $emptyString = '';

        $compressedString = Str::compress($emptyString);
        $this->assertEquals(gzencode($emptyString), $compressedString);

        $decompressedString = Str::decompress($compressedString);
        $this->assertEquals($emptyString, $decompressedString);
    }

    #[Test]
    public function it_can_handle_long_strings(): void
    {
        $longString = str_repeat('abcdefghijklmnopqrstuvwxyz', 1000);

        $compressedString = Str::compress($longString);
        $this->assertNotEquals($longString, $compressedString);
        $this->assertLessThan(strlen($longString), strlen($compressedString));

        $decompressedString = Str::decompress($compressedString);
        $this->assertEquals($longString, $decompressedString);
    }

    #[Test]
    #[DataProvider('compressionDataProvider')]
    public function it_can_compress_and_decompress_string(string $data): void
    {
        $compressed = Str::compress($data);
        $this->assertNotEquals($data, $compressed);

        $decompressed = Str::decompress($compressed);
        $this->assertEquals($data, $decompressed);
    }

    #[Test]
    #[DataProvider('driverDataProvider')]
    public function it_can_compress_and_decompress_string_with_specific_driver(string $driver): void
    {
        $data = 'This is a test string for compression with a specific driver.';

        $compressed = Str::compress($data, $driver);
        $this->assertNotEquals($data, $compressed);

        $decompressed = Str::decompress($compressed, $driver);
        $this->assertEquals($data, $decompressed);
    }

    public static function compressionDataProvider(): array
    {
        return [
            'short string' => ['Hello, World!'],
            'empty string' => [''],
            'long string' => [str_repeat('Lorem ipsum dolor sit amet. ', 100)],
        ];
    }

    public static function driverDataProvider(): array
    {
        return [
            'gzip driver' => ['gzip'],
            'zlib driver' => ['zlib'],
        ];
    }

    #[Test]
    public function it_can_handle_large_data(): void
    {
        $largeData = str_repeat('Large data for compression test. ', 10000);

        $compressed = Str::compress($largeData);
        $this->assertLessThan(strlen($largeData), strlen($compressed));

        $decompressed = Str::decompress($compressed);
        $this->assertEquals($largeData, $decompressed);
    }

    #[Test]
    public function it_uses_default_driver_when_not_specified(): void
    {
        $data = 'Test string for default driver.';

        // Set the default driver to 'zlib' for this test
        config(['pinimize.compression.default' => 'zlib']);

        $compressed = Str::compress($data);
        $this->assertNotEquals($data, $compressed);

        // Verify that it's not gzip compressed (gzip has a specific header)
        $this->assertStringNotContainsString("\x1f\x8b\x08", $compressed);

        $decompressed = Str::decompress($compressed);
        $this->assertEquals($data, $decompressed);
    }
}
