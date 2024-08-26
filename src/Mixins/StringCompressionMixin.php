<?php

declare(strict_types=1);

namespace Pinimize\Mixins;

use Pinimize\Facades\Compression;
use Pinimize\Facades\Decompression;

class StringCompressionMixin
{
    public function compress()
    {
        return function ($string) {
            return Compression::string($string);
        };
    }

    public function decompress()
    {
        return function ($string) {
            return Decompression::string($string);
        };
    }
}
