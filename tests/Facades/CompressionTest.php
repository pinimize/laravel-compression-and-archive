<?php

namespace Pinimize\Tests\Facades;

use Illuminate\Support\Facades\App;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Pinimize\Facades\Compression;
use Pinimize\Managers\CompressionManager;
use Pinimize\Tests\TestCase;

class CompressionTest extends TestCase
{
    #[Test]
    public function it_resolves_the_facade_accessor()
    {
        $this->assertInstanceOf(CompressionManager::class, App::make('pinimize.compression'));
    }

    #[Test]
    public function it_can_call_driver_method()
    {
        $managerMock = $this->mock(CompressionManager::class, function (MockInterface $mock) {
            $mock->shouldReceive('driver')->with('gzip')->once()->andReturn($mock);
        });

        App::instance('pinimize.compression', $managerMock);

        Compression::driver('gzip');
    }

    #[Test]
    public function it_can_call_extend_method()
    {
        $callback = function () {};
        $managerMock = $this->mock(CompressionManager::class, function (MockInterface $mock) use ($callback) {
            $mock->shouldReceive('extend')->with('custom', $callback)->once();
        });

        App::instance('pinimize.compression', $managerMock);

        Compression::extend('custom', $callback);
    }

    #[Test]
    public function it_can_call_methods_on_default_driver()
    {
        $result = Compression::string('data');
        $this->assertNotEquals('data', $result);
        $this->assertEquals(gzencode('data'), $result);
    }
}
