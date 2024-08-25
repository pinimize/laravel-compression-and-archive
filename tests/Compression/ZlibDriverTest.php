<?php

declare(strict_types=1);

namespace Pinimize\Tests\Compression;

use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Pinimize\Compression\ZlibDriver;
use Pinimize\Tests\TestCase;

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
        $config = ['level' => 6, 'encoding' => ZLIB_ENCODING_DEFLATE, 'disk' => null];
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

    #[Test]
    public function it_can_calculate_compression_ratio(): void
    {
        $original = str_repeat('Hello, World! ', 100);
        $compressed = $this->zlibDriver->string($original);

        $ratio = $this->zlibDriver->getRatio($original, $compressed);

        $this->assertGreaterThan(0, $ratio);
        $this->assertLessThan(1, $ratio);
    }

    #[Test]
    public function it_returns_zero_ratio_for_empty_string(): void
    {
        $ratio = $this->zlibDriver->getRatio('', '');
        $this->assertEquals(0.0, $ratio);
    }

    #[Test]
    public function it_returns_supported_algorithms(): void
    {
        $algorithms = $this->zlibDriver->getSupportedAlgorithms();

        $this->assertContains(ZLIB_ENCODING_RAW, $algorithms);
        $this->assertContains(ZLIB_ENCODING_GZIP, $algorithms);
        $this->assertContains(ZLIB_ENCODING_DEFLATE, $algorithms);
        $this->assertCount(3, $algorithms);
    }

    #[Test]
    public function it_can_compress_file(): void
    {
        $tempDir = sys_get_temp_dir();
        $sourceFile = $tempDir.'/zlib_test_source.txt';
        $destFile = $tempDir.'/zlib_test_dest.txt.zz';
        $sourceContent = str_repeat('Hello, World! ', 1000);
        file_put_contents($sourceFile, $sourceContent);

        $result = $this->zlibDriver->put($destFile, $sourceFile);

        $this->assertTrue($result);
        $this->assertFileExists($destFile);
        $this->assertLessThan(
            filesize($sourceFile),
            filesize($destFile)
        );

        // Verify the compressed content
        $decompressedContent = zlib_decode(file_get_contents($destFile));
        $this->assertEquals($sourceContent, $decompressedContent);

        // Clean up
        unlink($sourceFile);
        unlink($destFile);
    }

    #[Test]
    public function it_can_compress_a_file_using_a_disk(): void
    {
        $filesystem = Storage::fake($disk = 'local');
        $sourceFile = '/zlib_test_source.txt';
        $destFile = '/zlib_test_dest.txt.zz';

        $originalData = str_repeat('Hello, World! ', 1000);
        $filesystem->put($sourceFile, $originalData);

        $result = $this->zlibDriver->put($destFile, $sourceFile, ['disk' => $disk]);

        $this->assertTrue($result);
        $this->assertTrue($filesystem->exists($destFile));
        $this->assertLessThan($filesystem->size($sourceFile), $filesystem->size($destFile));
        $this->assertEquals($originalData, zlib_decode($filesystem->get($destFile)));
    }

    #[Test]
    public function it_compresses_file_with_custom_options(): void
    {
        $tempDir = sys_get_temp_dir();
        $sourceFile = tempnam($tempDir, 'zlib_test_source');
        $destFile1 = tempnam($tempDir, 'zlib_test_dest1');
        $destFile2 = tempnam($tempDir, 'zlib_test_dest2');

        $sourceContent = str_repeat('Hello, World! ', 1000);
        file_put_contents($sourceFile, $sourceContent);

        $this->zlibDriver->put($destFile1, $sourceFile, ['level' => 1]);
        $this->zlibDriver->put($destFile2, $sourceFile, ['level' => 9]);

        $this->assertLessThan(
            filesize($destFile1),
            filesize($destFile2),
        );

        // Clean up
        unlink($sourceFile);
        unlink($destFile1);
        unlink($destFile2);
    }
}
