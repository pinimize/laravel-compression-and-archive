<?php

namespace Pinimize\Tests\Facades;

use Illuminate\Support\Facades\App;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Pinimize\Facades\Decompression;
use Pinimize\Managers\DecompressionManager;
use Pinimize\Tests\TestCase;

class DecompressionTest extends TestCase
{
    #[Test]
    public function it_resolves_the_facade_accessor(): void
    {
        $this->assertInstanceOf(DecompressionManager::class, App::make('pinimize.decompression'));
    }

    #[Test]
    public function it_can_call_driver_method(): void
    {
        $managerMock = $this->mock(DecompressionManager::class, function (MockInterface $mock): void {
            $mock->shouldReceive('driver')->with('gzip')->once()->andReturn($mock);
        });

        App::instance('pinimize.decompression', $managerMock);

        Decompression::driver('gzip');
    }

    #[Test]
    public function it_can_call_extend_method(): void
    {
        $callback = function (): void {};
        $managerMock = $this->mock(DecompressionManager::class, function (MockInterface $mock) use ($callback): void {
            $mock->shouldReceive('extend')->with('custom', $callback)->once();
        });

        App::instance('pinimize.decompression', $managerMock);

        Decompression::extend('custom', $callback);
    }

    #[Test]
    public function it_can_call_methods_on_default_driver(): void
    {
        $original = 'Hello, world!';
        $compressed = gzencode($original);
        $decompressed = Decompression::string($compressed);
        $this->assertNotEquals($compressed, $decompressed);
        $this->assertEquals($original, $decompressed);
    }
}
