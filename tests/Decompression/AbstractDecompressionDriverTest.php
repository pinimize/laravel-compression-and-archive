<?php

namespace Pinimize\Tests\Decompression;

use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Pinimize\Decompression\AbstractDecompressionDriver;
use Pinimize\Tests\TestCase;
use RuntimeException;
use stdClass;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AbstractDecompressionDriverTest extends TestCase
{
    private $decompressionDriver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->decompressionDriver = new class(['level' => -1]) extends AbstractDecompressionDriver
        {
            public function getDefaultEncoding(): int
            {
                return ZLIB_ENCODING_DEFLATE;
            }

            protected function decompressString(string $string, array $options): string
            {
                return 'Decompressed: '.$string;
            }

            protected function decompressStream($input, $output, array $options): void
            {
                fwrite($output, 'Decompressed: ');

                while (! feof($input)) {
                    $chunk = fread($input, 8192);
                    if ($chunk === false) {
                        throw new RuntimeException('Failed to read from input stream');
                    }

                    fwrite($output, $chunk);
                }
            }
        };
    }

    #[Test]
    public function it_can_decompress_a_string_to_string()
    {
        $result = $this->decompressionDriver->string('Compressed Data');
        $this->assertEquals('Decompressed: Compressed Data', $result);
    }

    #[Test]
    public function it_can_decompress_a_resource_to_string()
    {
        $resource = fopen('php://temp', 'r+');
        fwrite($resource, 'Compressed Data');
        rewind($resource);

        $result = $this->decompressionDriver->string($resource);
        $this->assertEquals('Decompressed: Compressed Data', $result);

        fclose($resource);
    }

    #[Test]
    public function it_can_decompress_a_string_to_a_resource()
    {
        $result = $this->decompressionDriver->resource('Compressed Data');
        $this->assertIsResource($result);
        $this->assertEquals('Decompressed: Compressed Data', stream_get_contents($result));
    }

    #[Test]
    public function it_can_decompress_a_resource_to_a_resource()
    {
        $resource = fopen('php://temp', 'r+');
        fwrite($resource, 'Compressed Data');
        rewind($resource);

        $result = $this->decompressionDriver->resource($resource);
        $this->assertIsResource($result);
        $this->assertEquals('Decompressed: Compressed Data', stream_get_contents($result));

        fclose($resource);
    }

    #[Test]
    public function it_can_decompress_a_string_to_a_file()
    {
        $path = sys_get_temp_dir().'/decompressed.txt';
        $result = $this->decompressionDriver->put($path, 'Compressed Data');

        $this->assertTrue($result);
        $this->assertTrue(file_exists($path));
        $this->assertEquals('Decompressed: Compressed Data', file_get_contents($path));
    }

    #[Test]
    public function it_can_decompress_a_string_to_a_file_using_a_disk()
    {
        // Mock the filesystem operations
        $storage = Storage::fake('local');

        $result = $this->decompressionDriver->put('decompressed.txt', 'Compressed Data', 'local');

        $this->assertTrue($result);
        $storage->assertExists('decompressed.txt');
        $this->assertEquals('Decompressed: Compressed Data', $storage->get('decompressed.txt'));
    }

    #[Test]
    public function it_can_decompress_an_uploaded_file()
    {
        $file = UploadedFile::fake()->create('test.txt', 100);
        $file->storeAs('', 'test.txt', ['disk' => 'local']);

        $result = $this->decompressionDriver->string($file);

        $this->assertStringStartsWith('Decompressed: ', $result);
    }

    #[Test]
    public function it_can_decompress_a_file_instance()
    {
        $filePath = sys_get_temp_dir().'/test_file.txt';
        file_put_contents($filePath, 'Compressed Data');
        $file = new File($filePath);

        $result = $this->decompressionDriver->string($file);

        $this->assertEquals('Decompressed: Compressed Data', $result);
    }

    #[Test]
    public function it_throws_exception_for_invalid_input()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid contents provided');

        $this->decompressionDriver->string(new stdClass);
    }

    #[Test]
    public function it_can_decompress_using_custom_options()
    {
        $result = $this->decompressionDriver->string('Compressed Data', ['encoding' => ZLIB_ENCODING_GZIP]);

        $this->assertEquals('Decompressed: Compressed Data', $result);
    }

    #[Test]
    public function it_can_download_a_decompressed_file()
    {
        $storage = Storage::fake('local');
        $storage->put('compressed.txt', 'Compressed Data');

        $response = $this->decompressionDriver->download('compressed.txt', 'decompressed.txt', [], 'local');

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('attachment; filename="decompressed.txt"', $response->headers->get('Content-Disposition'));
    }

    #[Test]
    public function it_throws_exception_when_downloading_non_existent_file()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File does not exist: non_existent.txt');

        $this->decompressionDriver->download('non_existent.txt');
    }

    #[Test]
    public function it_can_handle_empty_input()
    {
        $result = $this->decompressionDriver->string('');

        $this->assertEquals('Decompressed: ', $result);
    }

    #[Test]
    public function it_can_decompress_large_data()
    {
        $largeData = str_repeat('Large compressed data. ', 1000);
        $resource = fopen('php://temp', 'r+');
        fwrite($resource, $largeData);
        rewind($resource);

        $result = $this->decompressionDriver->string($resource);

        $this->assertStringStartsWith('Decompressed: Large compressed data.', $result);
        $this->assertEquals(strlen($largeData) + 14, strlen($result)); // 14 is the length of 'Decompressed: '

        fclose($resource);
    }

    #[Test]
    public function it_respects_config_options()
    {
        $customDriver = new class(['level' => 6, 'encoding' => ZLIB_ENCODING_GZIP]) extends AbstractDecompressionDriver
        {
            public function getDefaultEncoding(): int
            {
                return ZLIB_ENCODING_GZIP;
            }

            protected function decompressString(string $string, array $options): string
            {
                $encoding = $options['encoding'] ?? $this->getDefaultEncoding();

                return "Decompressed with encoding {$encoding}: ".$string;
            }

            protected function decompressStream($input, $output, array $options): void
            {
                fwrite($output, "Decompressed with encoding {$options['encoding']}: ");
                stream_copy_to_stream($input, $output);
            }
        };

        $result = $customDriver->string('Compressed Data');

        $this->assertEquals('Decompressed with encoding '.ZLIB_ENCODING_GZIP.': Compressed Data', $result);
    }
}
