<?php

declare(strict_types=1);

namespace Pinimize\Tests\Mixins;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Pinimize\Facades\Compression;
use Pinimize\Facades\Decompression;
use Pinimize\Mixins\StringCompressionMixin;
use Pinimize\Tests\TestCase;

class StringCompressionMixinTest extends TestCase
{
    use DatabaseTransactions;

    private StringCompressionMixin $mixin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mixin = new StringCompressionMixin;
    }

    #[Test]
    public function it_can_return_a_compress_closure(): void
    {
        $compress = $this->mixin->compress();
        $this->assertIsCallable($compress);
    }

    #[Test]
    public function it_can_return_a_decompress_closure(): void
    {
        $decompress = $this->mixin->decompress();
        $this->assertIsCallable($decompress);
    }

    #[Test]
    public function it_can_compress_a_string_using_facade(): void
    {
        $compress = $this->mixin->compress();
        $string = 'Test string';

        Compression::shouldReceive('string')
            ->once()
            ->with($string)
            ->andReturn('compressed_string');

        $result = $compress($string);
        $this->assertEquals('compressed_string', $result);
    }

    #[Test]
    public function it_can_decompress_a_string_using_facade(): void
    {
        $decompress = $this->mixin->decompress();
        $compressedString = 'compressed_string';

        Decompression::shouldReceive('string')
            ->once()
            ->with($compressedString)
            ->andReturn('Test string');

        $result = $decompress($compressedString);
        $this->assertEquals('Test string', $result);
    }

    #[Test]
    public function it_can_compress_and_decompress_a_string(): void
    {
        $originalString = 'This is a test string for compression and decompression.';

        $compressedString = Str::compress($originalString);
        $this->assertNotEquals($originalString, $compressedString);

        $decompressedString = Str::decompress($compressedString);
        $this->assertEquals($originalString, $decompressedString);
    }

    #[Test]
    public function it_can_handle_empty_string(): void
    {
        $emptyString = '';

        $compressedString = Str::compress($emptyString);
        $this->assertEquals(gzencode($emptyString), $compressedString);

        $decompressedString = Str::decompress($compressedString);
        $this->assertEquals($emptyString, $decompressedString);
    }

    #[Test]
    public function it_can_handle_long_strings(): void
    {
        $longString = str_repeat('abcdefghijklmnopqrstuvwxyz', 1000);

        $compressedString = Str::compress($longString);
        $this->assertNotEquals($longString, $compressedString);
        $this->assertLessThan(strlen($longString), strlen($compressedString));

        $decompressedString = Str::decompress($compressedString);
        $this->assertEquals($longString, $decompressedString);
    }
}
