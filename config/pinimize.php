<?php

declare(strict_types=1);

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
    |
    | Disk:
    |   The disk where the files are located and will be stored. If the disk is set to `null`, then the local filesystem
    |   will be used.
    */
    'compression' => [
        'default' => env('COMPRESSION_DRIVER', 'gzip'),
        'mixin' => env('COMPRESSION_REGISTER_MIXIN', true),
        'drivers' => [
            'gzip' => [
                'level' => env('GZIP_LEVEL', -1),
                'encoding' => FORCE_GZIP,
                'disk' => env('COMPRESSION_DISK', null),
            ],
            'zlib' => [
                'level' => env('ZLIB_LEVEL', -1),
                'encoding' => ZLIB_ENCODING_DEFLATE,
                'disk' => env('COMPRESSION_DISK', null),
            ],
        ],
    ],
];
