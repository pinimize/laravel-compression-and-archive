<?php

declare(strict_types=1);

namespace Pinimize\Providers;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Pinimize\Managers\CompressionManager;
use Pinimize\Managers\DecompressionManager;
use Pinimize\Mixins\StringCompressionMixin;

class PinimizeServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/pinimize.php', 'pinimize');

        $this->app->singleton('pinimize.compression', fn (Container $container): CompressionManager => new CompressionManager($container));

        $this->app->singleton('pinimize.decompression', fn (Container $container): DecompressionManager => new DecompressionManager($container));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/pinimize.php' => config_path('pinimize.php'),
            ], 'pinimize-config');
        }

        if (config('pinimize.compression.mixin')) {
            Str::mixin(new StringCompressionMixin);
        }
    }
}
