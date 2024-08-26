<?php

namespace Pinimize\Tests\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Pinimize\Managers\CompressionManager;
use Pinimize\Managers\DecompressionManager;
use Pinimize\Providers\PinimizeServiceProvider;
use Pinimize\Tests\TestCase;

class PinimizeServiceProviderTest extends TestCase
{
    protected PinimizeServiceProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        // Remove the published config file if it exists
        if (File::exists(config_path('pinimize.php'))) {
            File::delete(config_path('pinimize.php'));
        }

        // Create a new instance of the service provider
        $this->provider = new PinimizeServiceProvider($this->app);
    }

    #[Test]
    public function it_merges_the_config(): void
    {
        // Manually call register and boot methods
        $this->provider->register();
        $this->provider->boot();

        // Assert that the config is merged
        $this->assertNotNull(config('pinimize'));
        $this->assertIsArray(config('pinimize'));

        // You can add more specific assertions here, for example:
        $this->assertArrayHasKey('compression', config('pinimize'));
    }

    #[Test]
    public function it_registers_the_singletons(): void
    {
        // Manually call register method
        $this->provider->register();

        $this->assertInstanceOf(CompressionManager::class, $this->app->make('pinimize.compression'));
        $this->assertInstanceOf(DecompressionManager::class, $this->app->make('pinimize.decompression'));
    }

    #[Test]
    public function it_publishes_the_config_file(): void
    {
        // Manually call boot method
        $this->provider->boot();

        $this->artisan('vendor:publish', ['--provider' => PinimizeServiceProvider::class, '--tag' => 'pinimize-config']);

        $this->assertFileExists(config_path('pinimize.php'));
    }

    #[Test]
    public function it_registers_compression_mixin_when_enabled(): void
    {
        putenv('COMPRESSION_REGISTER_MIXIN=true');
        Str::flushMacros();
        $this->refreshApplication();

        $this->assertTrue(config('pinimize.compression.mixin'));
        $this->assertTrue(Str::hasMacro('compress'), 'StringCompressionMixin mixin should be registered when enabled');
        $this->assertTrue(Str::hasMacro('decompress'), 'StringCompressionMixin mixin should be registered when enabled');
    }

    #[Test]
    public function it_does_not_register_compression_mixin_when_disabled(): void
    {
        putenv('COMPRESSION_REGISTER_MIXIN=false');
        Str::flushMacros();
        $this->refreshApplication();

        $this->assertFalse(config('pinimize.compression.mixin'));
        $this->assertFalse(Str::hasMacro('compress'), 'StringCompressionMixin mixin should not be registered when disabled');
        $this->assertFalse(Str::hasMacro('decompress'), 'StringCompressionMixin mixin should not be registered when disabled');
    }

    protected function tearDown(): void
    {
        // Remove the published config file after tests
        if (File::exists(config_path('pinimize.php'))) {
            File::delete(config_path('pinimize.php'));
        }

        parent::tearDown();
    }
}
