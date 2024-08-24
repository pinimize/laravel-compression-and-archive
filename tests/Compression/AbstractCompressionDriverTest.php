<?php

declare(strict_types=1);

namespace Pinimize\Tests\Compression;

use PHPUnit\Framework\TestCase;
use Pinimize\Compression\AbstractCompressionDriver;
use RuntimeException;
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

    public function testDownloadReturnsStreamedResponse(): void
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

    public function testDownloadWithCustomNameAndHeaders(): void
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

    public function testDownloadNonExistentFile(): void
    {
        $this->expectException(RuntimeException::class);
        $this->compressionDriver->download('non_existent_file.txt');
    }

    public function testDownloadWithCompressionOptions(): void
    {
        $path = __DIR__.'/../Fixtures/test.txt';
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

        $streamedResponse = $driver->download($path, null, [], ['level' => 9]);

        ob_start();
        $streamedResponse->sendContent();
        $content = ob_get_clean();

        $this->assertEquals('compressed_with_options_Test content', $content);

        unlink($path);
    }
}
