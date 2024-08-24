<?php

declare(strict_types=1);

namespace Pinimize\Tests\Compression;

use PHPUnit\Framework\TestCase;
use Pinimize\Compression\AbstractCompressionDriver;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AbstractCompressionDriverTest extends TestCase
{
    private $driver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->driver = new class(['level' => -1]) extends AbstractCompressionDriver
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

    public function testDownloadReturnsStreamedResponse()
    {
        $path = __DIR__.'/../Fixtures/data.csv';

        $response = $this->driver->download($path);

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/octet-stream', $response->headers->get('Content-Type'));
        $this->assertEquals('attachment; filename="data.csv.compressed"', $response->headers->get('Content-Disposition'));

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertEquals(
            zlib_encode(file_get_contents($path), $this->driver->getConfig()['encoding']),
            $content,
        );
    }

    public function testDownloadWithCustomNameAndHeaders()
    {
        $path = __DIR__.'/fixtures/test.txt';
        file_put_contents($path, 'Test content');

        $response = $this->driver->download($path, 'custom.txt', ['X-Custom-Header' => 'Value']);

        $this->assertEquals('attachment; filename="custom.txt"', $response->headers->get('Content-Disposition'));
        $this->assertEquals('Value', $response->headers->get('X-Custom-Header'));

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertEquals('compressed_Test content', $content);

        unlink($path);
    }

    public function testDownloadNonExistentFile()
    {
        $this->expectException(\RuntimeException::class);
        $this->driver->download('non_existent_file.txt');
    }

    public function testDownloadWithCompressionOptions()
    {
        $path = __DIR__.'/fixtures/test.txt';
        file_put_contents($path, 'Test content');

        $driver = new class(['level' => -1]) extends AbstractCompressionDriver
        {
            public function getDefaultEncoding(): int
            {
                return ZLIB_ENCODING_DEFLATE;
            }

            protected function compressString(string $string, int $level, int $encoding): string
            {
                return 'compressed_with_options_'.$string;
            }

            public function getSupportedAlgorithms(): array
            {
                return [ZLIB_ENCODING_DEFLATE];
            }

            public function getFileExtension(): string
            {
                return 'compressed';
            }

            public function resource($resource, array $options = [])
            {
                $content = stream_get_contents($resource);
                $compressed = $this->compressString($content, $options['level'] ?? -1, $this->getDefaultEncoding());
                $stream = fopen('php://temp', 'r+');
                fwrite($stream, $compressed);
                rewind($stream);

                return $stream;
            }
        };

        $response = $driver->download($path, null, [], ['level' => 9]);

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertEquals('compressed_with_options_Test content', $content);

        unlink($path);
    }
}
