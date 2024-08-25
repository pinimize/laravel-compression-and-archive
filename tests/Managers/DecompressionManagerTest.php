<?php

namespace Pinimize\Tests\Managers;

use PHPUnit\Framework\Attributes\Test;
use Pinimize\Decompression\GzipDriver;
use Pinimize\Decompression\ZlibDriver;
use Pinimize\Managers\DecompressionManager;
use Pinimize\Tests\TestCase;

class DecompressionManagerTest extends TestCase
{
    private DecompressionManager $decompressionManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->decompressionManager = $this->app->make(DecompressionManager::class);
    }

    #[Test]
    public function it_returns_the_correct_default_driver(): void
    {
        config(['pinimize.compression.default' => 'gzip']);

        $driver = $this->decompressionManager->driver();

        $this->assertSame(GzipDriver::class, $driver::class);
        $this->assertInstanceOf(GzipDriver::class, $driver);
    }

    #[Test]
    public function it_returns_the_correct_default_driver_from_config(): void
    {
        config(['pinimize.compression.default' => 'zlib']);

        $driver = $this->decompressionManager->driver();

        $this->assertSame(ZlibDriver::class, $driver::class);
        $this->assertInstanceOf(ZlibDriver::class, $driver);
    }

    #[Test]
    public function it_can_get_default_driver(): void
    {
        $this->assertEquals('gzip', $this->decompressionManager->getDefaultDriver());
    }

    #[Test]
    public function it_can_create_gzip_driver(): void
    {
        $gzipDriver = $this->decompressionManager->createGzipDriver();
        $this->assertInstanceOf(GzipDriver::class, $gzipDriver);
    }

    #[Test]
    public function it_can_create_zlib_driver(): void
    {
        $zlibDriver = $this->decompressionManager->createZlibDriver();
        $this->assertInstanceOf(ZlibDriver::class, $zlibDriver);
    }

    #[Test]
    public function it_can_get_compression_driver(): void
    {
        $driver = $this->decompressionManager->driver();
        $this->assertInstanceOf(GzipDriver::class, $driver);

        $driver = $this->decompressionManager->driver('zlib');
        $this->assertInstanceOf(ZlibDriver::class, $driver);
    }
}
