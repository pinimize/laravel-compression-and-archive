<?php

namespace Pinimize\Tests\Managers;

use PHPUnit\Framework\Attributes\Test;
use Pinimize\Compression\GzipDriver;
use Pinimize\Compression\ZlibDriver;
use Pinimize\Managers\CompressionManager;
use Pinimize\Tests\TestCase;

class CompressionManagerTest extends TestCase
{
    private CompressionManager $compressionManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->compressionManager = $this->app->make(CompressionManager::class);
    }

    #[Test]
    public function it_returns_the_correct_default_driver(): void
    {
        config(['pinimize.compression.default' => 'gzip']);

        $driver = $this->compressionManager->driver();

        $this->assertSame(GzipDriver::class, $driver::class);
        $this->assertInstanceOf(GzipDriver::class, $driver);
    }

    #[Test]
    public function it_returns_the_correct_default_driver_from_config(): void
    {
        config(['pinimize.compression.default' => 'zlib']);

        $driver = $this->compressionManager->driver();

        $this->assertSame(ZlibDriver::class, $driver::class);
        $this->assertInstanceOf(ZlibDriver::class, $driver);
    }

    #[Test]
    public function it_can_get_default_driver(): void
    {
        $this->assertEquals('gzip', $this->compressionManager->getDefaultDriver());
    }

    #[Test]
    public function it_can_create_gzip_driver(): void
    {
        $compressionContract = $this->compressionManager->createGzipDriver();
        $this->assertInstanceOf(GzipDriver::class, $compressionContract);
    }

    #[Test]
    public function it_can_create_zlib_driver(): void
    {
        $compressionContract = $this->compressionManager->createZlibDriver();
        $this->assertInstanceOf(ZlibDriver::class, $compressionContract);
    }

    #[Test]
    public function it_can_get_compression_driver(): void
    {
        $driver = $this->compressionManager->driver();
        $this->assertInstanceOf(GzipDriver::class, $driver);

        $driver = $this->compressionManager->driver('zlib');
        $this->assertInstanceOf(ZlibDriver::class, $driver);
    }
}
