<?php

declare(strict_types=1);

namespace Pinimize\Tests\Compression;

use GuzzleHttp\Psr7\Stream;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Pinimize\Compression\GzipDriver;
use Pinimize\Tests\TestCase;

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
    #[DataProvider('contentsProvider')]
    public function it_can_compress_data_to_a_string($contents, string $expected): void
    {
        if (is_callable($contents)) {
            $contents = $contents();
        }

        $compressed = $this->gzipDriver->string($contents);

        $this->assertNotEquals($expected, $compressed);
        $this->assertStringStartsWith("\x1f\x8b\x08", $compressed); // Gzip magic number
        $this->assertEquals($expected, gzdecode($compressed));        // Close the temporary file if it was opened
        if (is_resource($contents)) {
            fclose($contents);
        }
    }

    public static function contentsProvider(): array
    {
        $content = file_get_contents($path = __DIR__.'/../Fixtures/data.json');

        return [
            'StreamInterface' => [
                function () use ($content): Stream {
                    $tempFile = tmpfile();
                    fwrite($tempFile, $content);
                    rewind($tempFile);

                    return new Stream($tempFile);
                },
                $content,
            ],
            'File' => [
                new File($path),
                $content,
            ],
            'UploadedFile' => [
                UploadedFile::fake()->createWithContent('test.txt', $content),
                $content,
            ],
            'string' => [
                'Test string content',
                'Test string content',
            ],
            'resource' => [
                function () use ($content) {
                    $tempFile = tmpfile();
                    fwrite($tempFile, $content);
                    rewind($tempFile);

                    return $tempFile;
                },
                $content,
            ],
        ];
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
    public function it_can_get_correct_file_extension(): void
    {
        $this->assertEquals('gz', $this->gzipDriver->getFileExtension());
    }

    #[Test]
    #[DataProvider('contentsProvider')]
    public function it_can_compress_a_resource($contents, string $expected): void
    {
        if (is_callable($contents)) {
            $contents = $contents();
        }

        $compressedResource = $this->gzipDriver->resource($contents, ['encoding' => ZLIB_ENCODING_GZIP]);

        $this->assertIsResource($compressedResource);
        $actualData = stream_get_contents($compressedResource);
        $this->assertStringStartsWith("\x1f\x8b\x08", $actualData); // Gzip magic number
        $this->gzipDriver->string($expected);

        $this->assertEquals($expected, gzdecode($actualData));

        // Clean up
        if (is_resource($contents)) {
            fclose($contents);
        }
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
        $sourceFile = $tempDir.'/gzip_test_source.txt';
        $destFile = $tempDir.'/gzip_test_dest.txt.gz';
        $originalData = str_repeat('Hello, World! ', 1000);
        file_put_contents($sourceFile, $originalData);

        $result = $this->gzipDriver->put($destFile, $sourceFile);

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
        $filesystem = Storage::fake($disk = 'local');
        $sourceFile = '/gzip_test_source.txt';
        $destFile = '/gzip_test_dest.txt.gz';

        $originalData = str_repeat('Hello, World! ', 1000);
        $filesystem->put($sourceFile, $originalData);

        $result = $this->gzipDriver->put($destFile, $sourceFile, ['disk' => $disk]);

        $this->assertTrue($result);
        $this->assertTrue($filesystem->exists($destFile));
        $this->assertLessThan($filesystem->size($sourceFile), $filesystem->size($destFile));
        $this->assertEquals($originalData, gzdecode($filesystem->get($destFile)));
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

        $this->gzipDriver->put($destFile1, $sourceFile, ['level' => 1]);
        $this->gzipDriver->put($destFile2, $sourceFile, ['level' => 9]);

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
