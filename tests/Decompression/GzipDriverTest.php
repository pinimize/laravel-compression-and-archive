<?php

declare(strict_types=1);

namespace Pinimize\Tests\Decompression;

use PHPUnit\Framework\Attributes\Test;
use Pinimize\Decompression\GzipDriver;
use Pinimize\Exceptions\InvalidCompressedDataException;
use Pinimize\Tests\TestCase;

class GzipDriverTest extends TestCase
{
    private GzipDriver $gzipDriver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gzipDriver = new GzipDriver([]);
    }

    #[Test]
    public function it_can_decompress_a_string(): void
    {
        $original = 'Hello, World!';

        $compressed = gzencode($original);
        $decompressed = $this->gzipDriver->string($compressed);

        $this->assertEquals($original, $decompressed);
    }

    #[Test]
    public function it_can_decompress_large_content(): void
    {
        $largeContent = str_repeat('Lorem ipsum dolor sit amet. ', 10000);
        $compressed = gzencode($largeContent);

        $decompressed = $this->gzipDriver->string($compressed);
        $this->assertEquals($largeContent, $decompressed);
    }

    #[Test]
    public function it_can_handle_empty_string(): void
    {
        $emptyString = '';
        $compressed = gzencode($emptyString);

        $decompressed = $this->gzipDriver->string($compressed);
        $this->assertEquals($emptyString, $decompressed);
    }

    #[Test]
    public function it_can_handle_invalid_compressed_data(): void
    {
        $this->expectException(InvalidCompressedDataException::class);
        $this->expectExceptionMessage('This is not valid gzip data');

        $invalidCompressedData = 'This is not valid gzip data';

        $this->gzipDriver->string($invalidCompressedData);
    }
}
