<?php

namespace Pinimize\Tests;

use Illuminate\Contracts\Foundation\Application;
use Orchestra\Testbench\TestCase as Orchestra;
use Pinimize\Facades\Archive;
use Pinimize\Facades\Compression;
use Pinimize\Providers\PinimizeServiceProvider;

class TestCase extends Orchestra
{
    /**
     * Load package alias.
     *
     * @param  Application  $app
     * @return array
     */
    protected function getPackageAliases($app)
    {
        return [
            'pinimize-archive' => Archive::class,
            'pinimize-compression' => Compression::class,
        ];
    }

    protected function getPackageProviders($app)
    {
        return [
            PinimizeServiceProvider::class,
        ];
    }
}
