<?php

declare(strict_types=1);

namespace Pinimize\Mixins;

use Pinimize\Facades\Compression;
use Pinimize\Facades\Decompression;

class StringCompressionMixin
{
    public function compress()
    {
        return function ($string, $driver = null) {
            return Compression::driver($driver)->string($string);
        };
    }

    public function decompress()
    {
        return function ($string, $driver = null) {
            return Decompression::driver($driver)->string($string);
        };
    }
}
