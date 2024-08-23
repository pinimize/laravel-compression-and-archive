<?php

namespace Pinimize\Facades;

use Closure;
use Illuminate\Support\Facades\Facade;
use Pinimize\Managers\CompressionManager;

/**
 * @method static CompressionManager driver($driver = null)
 * @method static CompressionManager extend($driver, Closure $callback)
 *
 * @see CompressionManager
 */
class Compression extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'pinimize.compression';
    }
}
