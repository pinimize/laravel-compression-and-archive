<?php

namespace Pinimize\Tests\Mixins;

use Exception;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Pinimize\Facades\Compression;
use Pinimize\Facades\Decompression;
use Pinimize\Tests\TestCase;

class StorageCompressionMixinTest extends TestCase
{
    public Filesystem $storage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->storage = Storage::fake('local');
    }

    #[Test]
    public function it_can_compress_a_file(): void
    {
        $content = 'This is a test file content.';
        $this->storage->put('test.txt', $content);

        $result = $this->storage->compress('test.txt', 'test.txt.gz');

        $this->assertTrue($result);
        $this->storage->assertExists('test.txt.gz');
        $this->assertNotEquals($content, $this->storage->get('test.txt.gz'));
    }

    #[Test]
    public function it_can_compress_a_file_with_default_destination(): void
    {
        $content = 'This is a test file content.';
        $this->storage->put('test.txt', $content);

        $result = $this->storage->compress('test.txt');

        $this->assertEquals('test.txt.gz', $result);
        $this->storage->assertExists('test.txt.gz');
        $this->assertNotEquals($content, $this->storage->get('test.txt.gz'));
    }

    #[Test]
    public function it_can_compress_a_file_and_delete_source(): void
    {
        $this->storage->put('test.txt', 'This is a test file content.');

        $result = $this->storage->compress('test.txt', null, true);

        $this->assertEquals('test.txt.gz', $result);
        $this->storage->assertExists('test.txt.gz');
        $this->assertFalse($this->storage->exists('test.txt'));
    }

    #[Test]
    public function it_can_decompress_a_file(): void
    {
        $content = 'This is a test file content.';
        $this->storage->put('test.txt', $content);
        $this->storage->compress('test.txt', 'test.txt.gz');

        $result = $this->storage->decompress('test.txt.gz', 'decompressed.txt');

        $this->assertTrue($result);
        $this->storage->assertExists('decompressed.txt');
        $this->assertEquals($content, $this->storage->get('decompressed.txt'));
    }

    #[Test]
    public function it_can_decompress_a_file_with_default_destination(): void
    {
        $content = 'This is a test file content.';
        $this->storage->put('test.txt', $content);
        $this->storage->compress('test.txt', 'test.txt.gz');

        $result = $this->storage->decompress('test.txt.gz');

        $this->assertEquals('test.txt', $result);
        $this->storage->assertExists('test.txt');
        $this->assertEquals($content, $this->storage->get('test.txt'));
    }

    #[Test]
    public function it_can_decompress_a_file_and_delete_source(): void
    {
        $content = 'This is a test file content.';
        $this->storage->put('test.txt', $content);
        $this->storage->compress('test.txt', 'test.txt.gz');

        $result = $this->storage->decompress('test.txt.gz', null, true);

        $this->assertEquals('test.txt', $result);
        $this->storage->assertExists('test.txt');
        $this->assertFalse($this->storage->exists('test.txt.gz'));
        $this->assertEquals($content, $this->storage->get('test.txt'));
    }

    #[Test]
    public function it_throws_exception_for_non_existent_source_file_on_compress(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->storage->compress('non_existent.txt', 'compressed.txt.gz');
    }

    #[Test]
    public function it_throws_exception_for_non_existent_source_file_on_decompress(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->storage->decompress('non_existent.txt.gz', 'decompressed.txt');
    }

    #[Test]
    public function it_returns_false_on_compression_failure(): void
    {
        $this->storage->put('test.txt', 'This is a test file content.');

        // Mock the Compression facade
        Compression::shouldReceive('driver->resource')
            ->once()
            ->andThrow(new Exception('Compression failed'));

        $result = $this->storage->compress('test.txt', 'test.txt.gz');

        $this->assertFalse($result);
    }

    #[Test]
    public function it_returns_false_on_decompression_failure(): void
    {
        $content = 'This is a test file content.';
        $this->storage->put('test.txt.gz', $content);

        // Mock the Decompression facade
        Decompression::shouldReceive('driver->resource')
            ->once()
            ->andThrow(new Exception('Decompression failed'));

        $result = $this->storage->decompress('test.txt.gz', 'decompressed.txt');

        $this->assertFalse($result);
    }

    #[Test]
    public function it_can_compress_a_file_with_specific_driver(): void
    {
        $content = 'This is a test file content.';
        $this->storage->put('test.txt', $content);

        $result = $this->storage->compress('test.txt', 'test.txt.gz', false, 'zlib');

        $this->assertTrue($result);
        $this->storage->assertExists('test.txt.gz');
        $this->assertNotEquals($content, $this->storage->get('test.txt.gz'));
    }

    #[Test]
    public function it_can_decompress_a_file_with_specific_driver(): void
    {
        $content = 'This is a test file content.';
        $this->storage->put('test.txt', $content);
        $this->storage->compress('test.txt', 'test.txt.gz', false, 'zlib');

        $result = $this->storage->decompress('test.txt.gz', 'decompressed.txt', false, 'zlib');

        $this->assertTrue($result);
        $this->storage->assertExists('decompressed.txt');
        $this->assertEquals($content, $this->storage->get('decompressed.txt'));
    }
}
