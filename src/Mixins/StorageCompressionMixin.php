<?php

namespace Pinimize\Mixins;

use Closure;
use Exception;
use Illuminate\Filesystem\FilesystemManager;
use InvalidArgumentException;
use Pinimize\Facades\Compression;
use Pinimize\Facades\Decompression;

class StorageCompressionMixin
{
    public function compress(): Closure
    {
        return function (string $source, ?string $destination = null, bool $deleteSource = false, ?string $driver = null): string|bool {
            /** @var FilesystemManager $this */
            $filesystemOperator = $this->getDriver();

            if (! $filesystemOperator->has($source)) {
                throw new InvalidArgumentException("Source file does not exist: {$source}");
            }

            $returnString = $destination === null;
            $destination ??= $source.'.gz';

            try {
                $sourceStream = $filesystemOperator->readStream($source);
                $compressedStream = Compression::driver($driver)->resource($sourceStream);
                $filesystemOperator->writeStream($destination, $compressedStream);
            } catch (Exception) {
                return false;
            }

            if ($deleteSource && $source !== $destination) {
                $filesystemOperator->delete($source);
            }

            if ($returnString) {
                return $destination;
            }

            return true;
        };
    }

    public function decompress(): Closure
    {
        return function (string $source, ?string $destination = null, bool $deleteSource = false, ?string $driver = null): string|bool {
            /** @var FilesystemManager $this */
            $filesystemOperator = $this->getDriver();

            if (! $filesystemOperator->has($source)) {
                throw new InvalidArgumentException("Source file does not exist: {$source}");
            }

            $returnString = $destination === null;
            $destination ??= preg_replace('/\.gz$/', '', $source);

            try {
                $sourceStream = $filesystemOperator->readStream($source);
                $decompressedStream = Decompression::driver($driver)->resource($sourceStream);
                $filesystemOperator->writeStream($destination, $decompressedStream);
            } catch (Exception) {
                return false;
            }

            if ($deleteSource && $source !== $destination) {
                $filesystemOperator->delete($source);
            }

            if ($returnString) {
                return $destination;
            }

            return true;
        };
    }
}
