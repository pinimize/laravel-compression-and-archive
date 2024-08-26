<?php

declare(strict_types=1);

namespace Pinimize\Mixins;

use Closure;
use Pinimize\Facades\Compression;
use Pinimize\Facades\Decompression;

class StringCompressionMixin
{
    public function compress(): Closure
    {
        return fn (string $string, ?string $driver = null): string => Compression::driver($driver)->string($string);
    }

    public function decompress(): Closure
    {
        return fn (string $string, ?string $driver = null): string => Decompression::driver($driver)->string($string);
    }
}
