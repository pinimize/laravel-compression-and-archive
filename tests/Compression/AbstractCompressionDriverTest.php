<?php

declare(strict_types=1);

namespace Pinimize\Tests\Compression;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Pinimize\Compression\AbstractCompressionDriver;
use Pinimize\Tests\TestCase;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use stdClass;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AbstractCompressionDriverTest extends TestCase
{
    private AbstractCompressionDriver $compressionDriver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->compressionDriver = new class(['level' => -1]) extends AbstractCompressionDriver
        {
            public function getDefaultEncoding(): int
            {
                return ZLIB_ENCODING_DEFLATE;
            }

            protected function compressString(string $string, int $level, int $encoding): string
            {
                return 'compressed_'.$string;
            }

            public function getSupportedAlgorithms(): array
            {
                return [ZLIB_ENCODING_DEFLATE];
            }

            public function getFileExtension(): string
            {
                return 'compressed';
            }
        };
    }

    #[Test]
    public function it_can_download_compressed_file(): void
    {
        $path = __DIR__.'/../Fixtures/data.csv';

        $streamedResponse = $this->compressionDriver->download($path);

        $this->assertInstanceOf(StreamedResponse::class, $streamedResponse);
        $this->assertEquals(200, $streamedResponse->getStatusCode());
        $this->assertEquals('application/octet-stream', $streamedResponse->headers->get('Content-Type'));
        $this->assertEquals('attachment; filename="data.csv.compressed"', $streamedResponse->headers->get('Content-Disposition'));

        ob_start();
        $streamedResponse->sendContent();
        $content = ob_get_clean();

        $this->assertEquals(
            zlib_encode(file_get_contents($path), $this->compressionDriver->getConfig()['encoding']),
            $content,
        );
    }

    #[Test]
    public function it_can_download_compressed_file_using_a_disk(): void
    {
        $filesystem = Storage::fake($disk = 'local');
        $fixtureData = file_get_contents(__DIR__.'/../Fixtures/data.csv');
        $filesystem->put($path = 'data.csv', $fixtureData);
        $streamedResponse = $this->compressionDriver->download($path, 'data.csv.compressed', [], ['disk' => $disk]);

        $this->assertInstanceOf(StreamedResponse::class, $streamedResponse);
        $this->assertEquals(200, $streamedResponse->getStatusCode());
        $this->assertEquals('application/octet-stream', $streamedResponse->headers->get('Content-Type'));
        $this->assertEquals('attachment; filename="data.csv.compressed"', $streamedResponse->headers->get('Content-Disposition'));

        ob_start();
        $streamedResponse->sendContent();
        $content = ob_get_clean();

        $this->assertEquals(
            zlib_encode($fixtureData, $this->compressionDriver->getConfig()['encoding']),
            $content,
        );
    }

    #[Test]
    public function it_can_download_with_custom_name_and_headers(): void
    {
        $path = __DIR__.'/../Fixtures/data.csv';

        $streamedResponse = $this->compressionDriver->download($path, 'custom.csv', ['X-Custom-Header' => 'Value']);

        $this->assertEquals('attachment; filename="custom.csv"', $streamedResponse->headers->get('Content-Disposition'));
        $this->assertEquals('Value', $streamedResponse->headers->get('X-Custom-Header'));

        ob_start();
        $streamedResponse->sendContent();
        $content = ob_get_clean();

        $this->assertEquals(
            zlib_encode(file_get_contents($path), $this->compressionDriver->getConfig()['encoding']),
            $content,
        );
    }

    #[Test]
    public function it_throws_exception_for_non_existent_file(): void
    {
        $this->expectException(RuntimeException::class);
        $this->compressionDriver->download('non_existent_file.txt');
    }

    #[Test]
    #[DataProvider('compressionLevelProvider')]
    public function it_can_compress_with_custom_options(int $level): void
    {
        $storage = Storage::fake();
        $content = 'Test content';
        $path = 'test.txt';
        $storage->put($path, $content);

        $streamedResponse = $this->compressionDriver->download($storage->path($path), null, [], ['level' => $level]);

        ob_start();
        $streamedResponse->sendContent();
        $compressedContent = ob_get_clean();

        $this->assertEquals(
            zlib_encode((string) $storage->get($path), $this->compressionDriver->getConfig()['encoding'], $level),
            $compressedContent,
        );
    }

    public static function compressionLevelProvider(): array
    {
        return [
            'low level' => [1],
            'medium level' => [5],
            'maximum level' => [9],
        ];
    }

    #[Test]
    public function it_can_put_compressed_string_content(): void
    {
        $content = 'Test content';
        $path = sys_get_temp_dir().'/compression_test_'.uniqid().'.txt';

        $result = $this->compressionDriver->put($path, $content);

        $this->assertTrue($result);
        $this->assertTrue(file_exists($path));
        $this->assertEquals('compressed_'.$content, file_get_contents($path));
    }

    #[Test]
    public function it_can_put_compressed_string_content_using_a_disk(): void
    {
        $filesystem = Storage::fake($disk = 'local');
        $content = 'Test content';
        $path = 'test.txt';

        $result = $this->compressionDriver->put($path, $content, ['disk' => $disk]);

        $this->assertTrue($result);
        $filesystem->assertExists($path);
        $this->assertEquals('compressed_'.$content, $filesystem->get($path));
    }

    #[Test]
    public function it_can_put_compressed_stream_content(): void
    {
        $content = 'Test stream content';
        $resource = tmpfile();
        fwrite($resource, $content);
        rewind($resource);
        $path = sys_get_temp_dir().'/compression_test_'.uniqid().'.txt';

        $stream = $this->mock(StreamInterface::class, function (MockInterface $mock) use ($resource): void {
            $mock->shouldReceive('detach')->once()->andReturn($resource);
        });

        $result = $this->compressionDriver->put($path, $stream);

        $this->assertTrue($result);
        $this->assertTrue(file_exists($path));
        $this->assertEquals(
            zlib_encode($content, ZLIB_ENCODING_DEFLATE),
            file_get_contents($path),
        );
    }

    #[Test]
    public function it_can_put_compressed_stream_content_using_a_disk(): void
    {
        $storage = Storage::fake($disk = 'local');

        $content = 'Test stream content';
        $resource = tmpfile();
        fwrite($resource, $content);
        rewind($resource);
        $path = uniqid().'.txt';

        $stream = $this->mock(StreamInterface::class, function (MockInterface $mock) use ($resource): void {
            $mock->shouldReceive('detach')->once()->andReturn($resource);
        });
        $result = $this->compressionDriver->put($path, $stream, ['disk' => $disk]);

        $this->assertTrue($result);
        $storage->assertExists($path);
        $this->assertEquals(
            zlib_encode($content, ZLIB_ENCODING_DEFLATE),
            $storage->get($path),
        );
    }

    #[Test]
    public function it_can_put_compressed_uploaded_file(): void
    {
        $content = 'Test uploaded file content';
        $uploadedFile = UploadedFile::fake()->createWithContent('upload.txt', $content);
        $path = sys_get_temp_dir().'/compression_test_'.uniqid().'.txt';

        $result = $this->compressionDriver->put($path, $uploadedFile);

        $this->assertTrue($result);
        $this->assertTrue(file_exists($path));
        $this->assertStringContainsString(
            zlib_encode($content, ZLIB_ENCODING_DEFLATE),
            file_get_contents($path),
        );
    }

    #[Test]
    public function it_can_put_compressed_uploaded_file_using_a_disk(): void
    {
        $storage = Storage::fake($disk = 'local');
        $content = 'Test uploaded file content';
        $uploadedFile = UploadedFile::fake()->createWithContent('upload.txt', $content);
        $path = 'upload.txt.zz';

        $result = $this->compressionDriver->put($path, $uploadedFile, ['disk' => $disk]);

        $this->assertTrue($result);
        $storage->assertExists($path);
        $this->assertStringContainsString(
            zlib_encode($content, ZLIB_ENCODING_DEFLATE),
            $storage->get($path),
        );
    }

    #[Test]
    public function it_can_put_compressed_resource(): void
    {
        $content = 'Test resource content';
        $resource = tmpfile();
        fwrite($resource, $content);
        rewind($resource);

        $path = sys_get_temp_dir().'/compression_test_'.uniqid().'.txt';

        $result = $this->compressionDriver->put($path, $resource);

        $this->assertTrue($result);
        $this->assertTrue(file_exists($path));
        $this->assertStringContainsString(
            zlib_encode($content, ZLIB_ENCODING_DEFLATE),
            file_get_contents($path),
        );
    }

    #[Test]
    public function it_can_put_compressed_resource_using_a_disk(): void
    {
        $storage = Storage::fake($disk = 'local');

        $content = 'Test resource content';
        $resource = tmpfile();
        fwrite($resource, $content);
        rewind($resource);

        $path = 'test_resource.txt';

        $result = $this->compressionDriver->put($path, $resource, ['disk' => $disk]);

        $this->assertTrue($result);
        $storage->assertExists($path);
        $this->assertStringContainsString(
            zlib_encode($content, ZLIB_ENCODING_DEFLATE),
            $storage->get($path),
        );
    }

    #[Test]
    public function it_throws_exception_for_unsupported_content_type(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported content type');

        $this->compressionDriver->put('test.txt', new stdClass);
    }

    #[Test]
    #[DataProvider('compressionLevelProvider')]
    public function it_can_put_with_custom_compression_options(int $level): void
    {
        $storage = Storage::fake($disk = 'local');

        $content = 'Test content with custom compression';
        $path = "test_level_{$level}.txt";

        $result = $this->compressionDriver->put($path, $content, ['level' => $level, 'disk' => $disk]);

        $this->assertTrue($result);
        $storage->assertExists($path);
        $this->assertEquals('compressed_'.$content, $storage->get($path));
    }

    #[Test]
    public function it_can_handle_large_files(): void
    {
        $storage = Storage::fake($disk = 'local');

        $largeContent = str_repeat('Large content ', 10000); // About 130KB of data
        $path = 'large_file.txt';

        $result = $this->compressionDriver->put($path, $largeContent, ['disk' => $disk]);

        $this->assertTrue($result);
        $storage->assertExists($path);
        $this->assertStringStartsWith('compressed_', $storage->get($path));
    }

    #[Test]
    public function it_can_overwrite_existing_file(): void
    {
        $storage = Storage::fake($disk = 'local');

        $initialContent = 'Initial content';
        $newContent = 'New content';
        $path = 'overwrite_test.txt';

        $this->compressionDriver->put($path, $initialContent, ['disk' => $disk]);
        $result = $this->compressionDriver->put($path, $newContent, ['disk' => $disk]);

        $this->assertTrue($result);
        $storage->assertExists($path);
        $this->assertEquals('compressed_'.$newContent, $storage->get($path));
    }
}
