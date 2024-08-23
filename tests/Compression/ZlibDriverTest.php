<?php

declare(strict_types=1);

namespace Pinimize\Tests\Compression;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Pinimize\Compression\ZlibDriver;
use Pinimize\Tests\TestCase;

class ZlibDriverTest extends TestCase
{
    private ZlibDriver $zlibDriver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->zlibDriver = new ZlibDriver(['level' => 6]);
    }

    #[Test]
    public function it_can_get_correct_file_extension(): void
    {
        $this->assertEquals('zz', $this->zlibDriver->getFileExtension());
    }

    #[Test]
    public function it_can_get_config(): void
    {
        $config = ['level' => 6, 'encoding' => ZLIB_ENCODING_DEFLATE];
        $zlibDriver = new ZlibDriver($config);
        $this->assertEquals($config, $zlibDriver->getConfig());
    }

    #[Test]
    #[DataProvider('compressionLevelProvider')]
    public function it_can_compress_string_with_different_levels(int $level): void
    {
        $zlibDriver = new ZlibDriver(['level' => $level]);
        $original = 'Hello, World! Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet. Consectetur adipiscing elit.';
        $compressed = $zlibDriver->string($original);

        $this->assertNotEquals($original, $compressed);
        if ($level === 0) {
            // String is not compressed, but it's still encoded and has headers
            $this->assertGreaterThan(strlen($original), strlen($compressed));
        } else {
            $this->assertLessThan(strlen($original), strlen($compressed));
        }

    }

    public static function compressionLevelProvider(): array
    {
        return [
            'default level' => [-1],
            'no compression' => [0],
            'best speed' => [1],
            'best compression' => [9],
        ];
    }

    #[Test]
    public function it_can_compress_large_strings(): void
    {
        $largeString = str_repeat('Lorem ipsum dolor sit amet. ', 10000);

        $compressed = $this->zlibDriver->string($largeString);
        $this->assertLessThan(strlen($largeString), strlen($compressed));

        $decompressed = $this->zlibDriver->decompressString($compressed);
        $this->assertEquals($largeString, $decompressed);
    }

    #[Test]
    public function it_can_handle_empty_string(): void
    {
        $emptyString = '';

        $compressed = $this->zlibDriver->string($emptyString);
        $this->assertNotEmpty($compressed);
    }

    #[Test]
    public function it_can_compress_with_custom_options(): void
    {
        $string = 'Test string';

        $compressed1 = $this->zlibDriver->string($string);
        $compressed2 = $this->zlibDriver->string($string, ['level' => 1]);

        $this->assertNotEquals($compressed1, $compressed2);
    }
}
