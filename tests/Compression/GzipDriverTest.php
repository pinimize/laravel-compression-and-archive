<?php

declare(strict_types=1);

namespace Pinimize\Tests\Compression;

use ErrorException;
use Illuminate\Support\Facades\Storage;
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
            'disk' => null,
        ]);
    }

    #[Test]
    public function it_can_compress_a_string(): void
    {
        $original = 'Hello, World!';
        $compressed = $this->gzipDriver->string($original);

        $this->assertNotEquals($original, $compressed);
        $this->assertStringStartsWith("\x1f\x8b\x08", $compressed); // Gzip magic number
        $this->assertEquals($original, gzdecode($compressed));
    }

    #[Test]
    #[DataProvider('compressionLevelProvider')]
    public function it_can_compress_with_different_levels(int $level): void
    {
        $gzipDriver = new GzipDriver(['level' => $level]);
        $original = str_repeat('A', 1000);
        $compressed = $gzipDriver->string($original);

        $this->assertLessThan(strlen($original), strlen($compressed));
        $this->assertEquals($original, gzdecode($compressed));
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
        $this->assertStringStartsWith("\x1f\x8b\x08", $actualData); // Gzip magic number
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
        $this->assertEquals($originalData, zlib_decode(stream_get_contents($compressedResource2)));

        // Clean up
        fclose($originalResource);
        fclose($compressedResource1);
        fclose($compressedResource2);
    }

    #[Test]
    public function it_can_get_config(): void
    {
        $config = $this->gzipDriver->getConfig();
        $this->assertIsArray($config);
        $this->assertArrayHasKey('level', $config);
        $this->assertArrayHasKey('disk', $config);
        $this->assertArrayHasKey('encoding', $config);
        $this->assertEquals(6, $config['level']);
        $this->assertEquals(FORCE_GZIP, $config['encoding']);
    }

    private function getResourceSize($resource): int
    {
        fseek($resource, 0, SEEK_END);
        $size = ftell($resource);
        rewind($resource);

        return $size;
    }

    #[Test]
    public function it_can_compress_a_file(): void
    {
        $tempDir = sys_get_temp_dir();
        $sourceFile = tempnam($tempDir, 'gzip_test_source');
        $destFile = tempnam($tempDir, 'gzip_test_dest');

        $originalData = str_repeat('Hello, World! ', 1000);
        file_put_contents($sourceFile, $originalData);

        $result = $this->gzipDriver->file($sourceFile, $destFile);

        $this->assertTrue($result);
        $this->assertFileExists($destFile);
        $this->assertLessThan(filesize($sourceFile), filesize($destFile));
        $this->assertEquals($originalData, gzdecode(file_get_contents($destFile)));

        unlink($sourceFile);
        unlink($destFile);
    }

    #[Test]
    public function it_can_compress_a_file_using_a_disk(): void
    {
        $storage = Storage::fake($disk = 'local');
        $tempDir = sys_get_temp_dir();
        $sourceFile = tempnam($tempDir, 'gzip_test_source');
        $destFile = tempnam($tempDir, 'gzip_test_dest');

        $originalData = str_repeat('Hello, World! ', 1000);
        $storage->put($sourceFile, $originalData);

        $result = $this->gzipDriver->file($sourceFile, $destFile, ['disk' => $disk]);

        $this->assertTrue($result);
        $this->assertTrue($storage->exists($destFile));
        $this->assertLessThan($storage->size($sourceFile), $storage->size($destFile));
        $this->assertEquals($originalData, gzdecode($storage->get($destFile)));
    }

    #[Test]
    public function it_throws_exception_for_non_existent_source_file(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Source file does not exist');

        $this->gzipDriver->file('non_existent_file.txt', 'output.gz');
    }

    #[Test]
    public function it_throws_exception_for_invalid_destination(): void
    {
        $tempDir = sys_get_temp_dir();
        $sourceFile = tempnam($tempDir, 'gzip_test_source');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to open output stream');

        // Set up error handling to convert warnings to exceptions
        set_error_handler(function ($severity, $message, $file, $line): void {
            throw new RuntimeException($message, $severity, new ErrorException($message, 0, $severity, $file, $line));
        });

        try {
            $this->gzipDriver->file($sourceFile, '/invalid/path/output.gz');
        } finally {
            // Restore the original error handler
            restore_error_handler();
            // Clean up the temporary file
            if (file_exists($sourceFile)) {
                unlink($sourceFile);
            }
        }
    }

    #[Test]
    public function it_compresses_file_with_custom_options(): void
    {
        $tempDir = sys_get_temp_dir();
        $sourceFile = tempnam($tempDir, 'gzip_test_source');
        $destFile1 = tempnam($tempDir, 'gzip_test_dest1');
        $destFile2 = tempnam($tempDir, 'gzip_test_dest2');

        $sourceContent = str_repeat('Hello, World! ', 1000);
        file_put_contents($sourceFile, $sourceContent);

        $this->gzipDriver->file($sourceFile, $destFile1, ['level' => 1]);
        $this->gzipDriver->file($sourceFile, $destFile2, ['level' => 9]);

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
