<?php

namespace Pinimize\Tests\Mixins;

use Exception;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Pinimize\Facades\Compression;
use Pinimize\Facades\Decompression;
use Pinimize\Tests\TestCase;

class StorageCompressionMixinTest extends TestCase
{
    use WithFaker;

    public Filesystem $storage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->storage = Storage::fake('local');
    }

    #[Test]
    public function it_can_compress_a_file(): void
    {
        $content = $this->faker->paragraph();
        $filepath = $this->faker->uuid().'.txt';
        $this->storage->put($filepath, $content);

        $result = $this->storage->compress($filepath, $filepath.'.gz');

        $this->assertTrue($result);
        $this->storage->assertExists($filepath.'.gz');
        $this->assertNotEquals($content, $this->storage->get($filepath.'.gz'));
    }

    #[Test]
    public function it_can_compress_a_file_with_default_destination(): void
    {
        $content = $this->faker->paragraph();
        $filepath = $this->faker->uuid().'.txt';
        $this->storage->put($filepath, $content);

        $result = $this->storage->compress($filepath, null, false, 'zlib');

        $this->assertEquals($filepath.'.zz', $result);
        $this->storage->assertExists($filepath.'.zz');
        $this->assertNotEquals($content, $this->storage->get($filepath.'.zz'));
    }

    #[Test]
    public function it_can_compress_a_file_and_delete_source(): void
    {
        $content = $this->faker->paragraph();
        $filepath = $this->faker->uuid().'.txt';
        $this->storage->put($filepath, $content);

        $result = $this->storage->compress($filepath, null, true);

        $this->assertEquals($filepath.'.gz', $result);
        $this->storage->assertExists($filepath.'.gz');
        $this->assertFalse($this->storage->exists($filepath));
    }

    #[Test]
    public function it_can_decompress_a_file(): void
    {
        $content = $this->faker->paragraph();
        $filepath = $this->faker->uuid().'.txt';
        $this->storage->put($filepath, $content);

        $this->storage->compress($filepath, $filepath.'.gz');

        $result = $this->storage->decompress($filepath.'.gz', 'decompressed.txt');

        $this->assertTrue($result);
        $this->storage->assertExists('decompressed.txt');
        $this->assertEquals($content, $this->storage->get('decompressed.txt'));
    }

    #[Test]
    public function it_can_decompress_a_file_with_default_destination(): void
    {
        $content = $this->faker->paragraph();
        $filepath = $this->faker->uuid().'.txt';
        $this->storage->put($filepath, $content);

        $compressed = $this->storage->compress($filepath, null, true, 'zlib');
        $filepath2 = $this->storage->decompress($compressed, null, true, 'zlib');

        $this->assertEquals($filepath, $filepath2);
        $this->storage->assertExists($filepath2);
        $this->assertEquals($content, $this->storage->get($filepath2));
    }

    #[Test]
    public function it_can_decompress_a_file_and_delete_source(): void
    {
        $content = $this->faker->paragraph();
        $filepath = $this->faker->uuid().'.txt';
        $this->storage->put($filepath, $content);

        $this->storage->compress($filepath, $filepath.'.gz');

        $result = $this->storage->decompress($filepath.'.gz', null, true);

        $this->assertEquals($filepath, $result);
        $this->storage->assertExists($filepath);
        $this->assertFalse($this->storage->exists($filepath.'.gz'));
        $this->assertEquals($content, $this->storage->get($filepath));
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
        $content = $this->faker->paragraph();
        $filepath = $this->faker->uuid().'.txt';
        $this->storage->put($filepath, $content);

        // Mock the Compression facade
        Compression::shouldReceive('driver->resource')
            ->once()
            ->andThrow(new Exception('Compression failed'));

        $result = $this->storage->compress($filepath, $filepath.'.gz');

        $this->assertFalse($result);
    }

    #[Test]
    public function it_returns_false_on_decompression_failure(): void
    {
        $content = $this->faker->paragraph();
        $filepath = $this->faker->uuid().'.txt';
        $this->storage->put($filepath, $content);

        // Mock the Decompression facade
        Decompression::shouldReceive('driver->resource')
            ->once()
            ->andThrow(new Exception('Decompression failed'));

        $result = $this->storage->decompress($filepath, 'decompressed.txt');

        $this->assertFalse($result);
    }

    #[Test]
    public function it_can_compress_a_file_with_specific_driver(): void
    {
        $content = $this->faker->paragraph();
        $filepath = $this->faker->uuid().'.txt';
        $this->storage->put($filepath, $content);

        $result = $this->storage->compress($filepath, $filepath.'.gz', false, 'zlib');

        $this->assertTrue($result);
        $this->storage->assertExists($filepath.'.gz');
        $this->assertNotEquals($content, $this->storage->get($filepath.'.gz'));
    }

    #[Test]
    public function it_can_decompress_a_file_with_specific_driver(): void
    {
        $content = $this->faker->paragraph();
        $filepath = $this->faker->uuid().'.txt';
        $this->storage->put($filepath, $content);

        $this->storage->compress($filepath, $filepath.'.gz', false, 'zlib');

        $result = $this->storage->decompress($filepath.'.gz', 'decompressed.txt', false, 'zlib');

        $this->assertTrue($result);
        $this->storage->assertExists('decompressed.txt');
        $this->assertEquals($content, $this->storage->get('decompressed.txt'));
    }
}
