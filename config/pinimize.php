<?php

declare(strict_types=1);

use Pinimize\Archive\TarDriver;
use Pinimize\Archive\ZipDriver;

return [
    /*
    |--------------------------------------------------------------------------
    | Compression and Decompression
    |--------------------------------------------------------------------------
    |
    | These options control the configuration for the compression and decompression drivers.
    |
    | Levels:
    |   -1: default
    |   0: no compression,
    |   1: fastest,
    |   9: best
    */
    'compression' => [
        'default' => env('COMPRESSION_DRIVER', 'gzip'),
        'drivers' => [
            'gzip' => [
                'level' => env('GZIP_LEVEL', -1),
                'encoding' => FORCE_GZIP,
            ],
            'zlib' => [
                'level' => env('ZLIB_LEVEL', -1),
                'encoding' => ZLIB_ENCODING_DEFLATE,
            ],
        ],
    ],
];
