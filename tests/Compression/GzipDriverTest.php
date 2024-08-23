<?php

declare(strict_types=1);

namespace Pinimize\Tests\Compression;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Pinimize\Compression\GzipDriver;
use Pinimize\Tests\TestCase;
use RuntimeException;

class GzipDriverTest extends TestCase
{
    private GzipDriver $gzipDriver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gzipDriver = new GzipDriver([
            'level' => 6,
            'encoding' => FORCE_GZIP,
        ]);
    }

    #[Test]
    public function it_can_compress_a_string(): void
    {
        $original = 'Hello, World!';
        $compressed = $this->gzipDriver->string($original);

        $this->assertNotEquals($original, $compressed);
        $this->assertStringStartsWith("\x1f\x8b\x08", $compressed); // Gzip magic number
    }

    #[Test]
    #[DataProvider('compressionLevelProvider')]
    public function it_can_compress_with_different_levels(int $level): void
    {
        $gzipDriver = new GzipDriver(['level' => $level]);
        $original = str_repeat('A', 1000);
        $compressed = $gzipDriver->string($original);

        $this->assertLessThan(strlen($original), strlen($compressed));
    }

    public static function compressionLevelProvider(): array
    {
        return [
            'low level' => [1],
            'default level' => [-1],
            'maximum level' => [9],
        ];
    }

    #[Test]
    public function it_can_get_correct_file_extension(): void
    {
        $this->assertEquals('gz', $this->gzipDriver->getFileExtension());
    }

    #[Test]
    public function it_can_compress_large_content(): void
    {
        $largeContent = str_repeat('Lorem ipsum dolor sit amet. ', 10000);

        $compressed = $this->gzipDriver->string($largeContent);
        $this->assertLessThan(strlen($largeContent), strlen($compressed));

    }

    #[Test]
    public function it_can_handle_empty_string(): void
    {
        $emptyString = '';

        $compressed = $this->gzipDriver->string($emptyString);
        $this->assertNotEmpty($compressed); // Gzip adds headers even for empty input
    }

    #[Test]
    public function it_can_merge_options_with_config(): void
    {
        $original = 'Test string';

        // Default compression (level 6)
        $compressed1 = $this->gzipDriver->string($original);

        // Custom compression level
        $compressed2 = $this->gzipDriver->string($original, ['level' => 1]);

        $this->assertNotEquals($compressed1, $compressed2);
    }

    #[Test]
    public function it_can_compress_a_resource(): void
    {
        $originalData = str_repeat('Hello, World! ', 1000);
        $originalResource = fopen('php://temp', 'r+');
        fwrite($originalResource, $originalData);
        rewind($originalResource);

        $compressedResource = $this->gzipDriver->resource($originalResource);

        $this->assertIsResource($compressedResource);
        $actualData = stream_get_contents($compressedResource);
        $expectedData = $this->gzipDriver->string($originalData);

        $this->assertEquals($expectedData, $actualData);
        $this->assertEquals($originalData, gzdecode($actualData));

        // Clean up
        fclose($originalResource);
        fclose($compressedResource);
    }

    #[Test]
    public function it_throws_exception_for_invalid_resource(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid resource provided');

        $this->gzipDriver->resource('not a resource');
    }

    #[Test]
    public function it_can_compress_with_custom_level(): void
    {
        $originalData = str_repeat('Hello, World! ', 1000);
        $originalResource = fopen('php://temp', 'r+');
        fwrite($originalResource, $originalData);
        rewind($originalResource);

        $compressedResource1 = $this->gzipDriver->resource($originalResource, ['encoding' => FORCE_GZIP]);
        rewind($originalResource);
        $compressedResource2 = $this->gzipDriver->resource($originalResource, ['level' => 1, 'encoding' => FORCE_DEFLATE]);

        $compressedSize1 = $this->getResourceSize($compressedResource1);
        $compressedSize2 = $this->getResourceSize($compressedResource2);

        $this->assertGreaterThan($compressedSize1, $compressedSize2);
        $this->assertIsResource($compressedResource1);
        $this->assertIsResource($compressedResource2);
        $this->assertEquals($originalData, gzdecode(stream_get_contents($compressedResource1)));
        $this->assertEquals($originalData, gzdecode(stream_get_contents($compressedResource2)));

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
