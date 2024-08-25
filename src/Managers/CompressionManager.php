<?php

declare(strict_types=1);

namespace Pinimize\Managers;

use Illuminate\Support\Manager;
use Pinimize\Compression\GzipDriver;
use Pinimize\Compression\ZlibDriver;
use Pinimize\Contracts\CompressionContract;

/**
 * @mixin CompressionContract
 *
 * @phpstan-import-type GzipConfigArray from GzipDriver
 * @phpstan-import-type ZlibConfigArray from ZlibDriver
 */
class CompressionManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return $this->config->get('pinimize.compression.default', 'gzip');
    }

    public function createGzipDriver(): CompressionContract
    {
        /** @var GzipConfigArray $config */
        $config = $this->getConfig('gzip');

        return new GzipDriver($config);
    }

    public function createZlibDriver(): CompressionContract
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
