<?php

declare(strict_types=1);

namespace Pinimize\Managers;

use Illuminate\Support\Manager;
use Pinimize\Contracts\DecompressionContract;
use Pinimize\Decompression\GzipDriver;
use Pinimize\Decompression\ZlibDriver;

/**
 * @mixin DecompressionContract
 *
 * @phpstan-import-type GzipConfigArray from GzipDriver
 * @phpstan-import-type ZlibConfigArray from ZlibDriver
 */
class DecompressionManager extends Manager
{
    public function getDefaultDriver()
    {
        return $this->config->get('pinimize.compression.default', 'gzip');
    }

    public function createGzipDriver(): GzipDriver
    {
        /** @var GzipConfigArray $config */
        $config = $this->getConfig('gzip');

        return new GzipDriver($config);
    }

    public function createZlibDriver(): ZlibDriver
    {
        /** @var ZlibConfigArray $config */
        $config = $this->getConfig('zlib');

        return new ZlibDriver($config);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getConfig(string $name): array
    {
        return $this->config->get("pinimize.compression.drivers.{$name}", []);
    }
}
