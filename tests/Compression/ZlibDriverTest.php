<?php

declare(strict_types=1);

namespace Pinimize\Tests\Compression;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Pinimize\Compression\ZlibDriver;
use Pinimize\Tests\TestCase;
use RuntimeException;

class ZlibDriverTest extends TestCase
{
    private ZlibDriver $zlibDriver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->zlibDriver = new ZlibDriver([
            'level' => 6,
            'encoding' => ZLIB_ENCODING_DEFLATE,
        ]);
    }

    #[Test]
    public function it_can_get_correct_file_extension(): void
    {
        $this->assertEquals('zz', $this->zlibDriver->getFileExtension());
    }

    #[Test]
    public function it_can_get_config(): void
    {
        $config = ['level' => 6, 'encoding' => ZLIB_ENCODING_DEFLATE];
        $zlibDriver = new ZlibDriver($config);
        $this->assertEquals($config, $zlibDriver->getConfig());
    }

    #[Test]
    #[DataProvider('compressionLevelProvider')]
    public function it_can_compress_string_with_different_levels(int $level): void
    {
        $zlibDriver = new ZlibDriver(['level' => $level]);
        $original = 'Hello, World! Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet. Consectetur adipiscing elit.';
        $compressed = $zlibDriver->string($original);

        $this->assertNotEquals($original, $compressed);
        if ($level === 0) {
            // String is not compressed, but it's still encoded and has headers
            $this->assertGreaterThan(strlen($original), strlen($compressed));
        } else {
            $this->assertLessThan(strlen($original), strlen($compressed));
        }

    }

    public static function compressionLevelProvider(): array
    {
        return [
            'default level' => [-1],
            'no compression' => [0],
            'best speed' => [1],
            'best compression' => [9],
        ];
    }

    #[Test]
    public function it_can_compress_large_strings(): void
    {
        $largeString = str_repeat('Lorem ipsum dolor sit amet. ', 10000);

        $compressed = $this->zlibDriver->string($largeString);
        $this->assertLessThan(strlen($largeString), strlen($compressed));
    }

    #[Test]
    public function it_can_handle_empty_string(): void
    {
        $emptyString = '';

        $compressed = $this->zlibDriver->string($emptyString);
        $this->assertNotEmpty($compressed);
    }

    #[Test]
    public function it_can_compress_with_custom_options(): void
    {
        $string = 'Test string';

        $compressed1 = $this->zlibDriver->string($string);
        $compressed2 = $this->zlibDriver->string($string, ['level' => 1]);

        $this->assertNotEquals($compressed1, $compressed2);
    }

    #[Test]
    public function it_can_compress_a_resource(): void
    {
        $originalData = str_repeat('Hello, World! ', 1000);
        $originalResource = fopen('php://temp', 'r+');
        fwrite($originalResource, $originalData);
        rewind($originalResource);

        $compressedResource = $this->zlibDriver->resource($originalResource);

        $this->assertIsResource($compressedResource);
        $actualData = stream_get_contents($compressedResource);
        $expectedData = $this->zlibDriver->string($originalData);

        $this->assertEquals($expectedData, $actualData);
        $this->assertEquals($originalData, zlib_decode($actualData));

        // Clean up
        fclose($originalResource);
        fclose($compressedResource);
    }

    #[Test]
    public function it_throws_exception_for_invalid_resource(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid resource provided');

        $this->zlibDriver->resource('not a resource');
    }

    #[Test]
    public function it_can_compress_with_custom_level(): void
    {
        $originalData = str_repeat('Hello, World! ', 1000);
        $originalResource = fopen('php://temp', 'r+');
        fwrite($originalResource, $originalData);
        rewind($originalResource);

        $compressedResource1 = $this->zlibDriver->resource($originalResource, ['encoding' => ZLIB_ENCODING_DEFLATE]);
        rewind($originalResource);
        $compressedResource2 = $this->zlibDriver->resource($originalResource, ['level' => 1, 'encoding' => ZLIB_ENCODING_DEFLATE]);

        $compressedSize1 = $this->getResourceSize($compressedResource1);
        $compressedSize2 = $this->getResourceSize($compressedResource2);

        $this->assertGreaterThan($compressedSize1, $compressedSize2);
        $this->assertIsResource($compressedResource1);
        $this->assertIsResource($compressedResource2);
        $this->assertEquals($originalData, zlib_decode(stream_get_contents($compressedResource1)));
        $this->assertEquals($originalData, zlib_decode(stream_get_contents($compressedResource2)));

        // Clean up
        fclose($originalResource);
        fclose($compressedResource1);
        fclose($compressedResource2);
    }

    private function getResourceSize($resource): int
    {
        fseek($resource, 0, SEEK_END);
        $size = ftell($resource);
        rewind($resource);

        return $size;
    }
}
