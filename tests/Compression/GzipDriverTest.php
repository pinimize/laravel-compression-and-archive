<?php

declare(strict_types=1);

namespace Pinimize\Tests\Compression;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Pinimize\Compression\GzipDriver;
use Pinimize\Tests\TestCase;

class GzipDriverTest extends TestCase
{
    #[Test]
    public function it_can_compress_a_string(): void
    {
        $gzipDriver = new GzipDriver(['level' => 6]);
        $original = 'Hello, World!';
        $compressed = $gzipDriver->string($original);

        $this->assertNotEquals($original, $compressed);
        $this->assertStringStartsWith("\x1f\x8b\x08", $compressed); // Gzip magic number
    }

    #[Test]
    #[DataProvider('compressionLevelProvider')]
    public function it_can_compress_with_different_levels(int $level): void
    {
        $gzipDriver = new GzipDriver(['level' => $level]);
        $original = str_repeat('A', 1000);
        $compressed = $gzipDriver->string($original);

        $this->assertLessThan(strlen($original), strlen($compressed));
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
        $gzipDriver = new GzipDriver(['level' => 6]);
        $this->assertEquals('gz', $gzipDriver->getFileExtension());
    }

    #[Test]
    public function it_can_compress_large_content(): void
    {
        $gzipDriver = new GzipDriver(['level' => 6]);
        $largeContent = str_repeat('Lorem ipsum dolor sit amet. ', 10000);

        $compressed = $gzipDriver->string($largeContent);
        $this->assertLessThan(strlen($largeContent), strlen($compressed));

    }

    #[Test]
    public function it_can_handle_empty_string(): void
    {
        $gzipDriver = new GzipDriver(['level' => 6]);
        $emptyString = '';

        $compressed = $gzipDriver->string($emptyString);
        $this->assertNotEmpty($compressed); // Gzip adds headers even for empty input
    }

    #[Test]
    public function it_can_merge_options_with_config(): void
    {
        $gzipDriver = new GzipDriver(['level' => 6]);
        $original = 'Test string';

        // Default compression (level 6)
        $compressed1 = $gzipDriver->string($original);

        // Custom compression level
        $compressed2 = $gzipDriver->string($original, ['level' => 1]);

        $this->assertNotEquals($compressed1, $compressed2);
    }
}
