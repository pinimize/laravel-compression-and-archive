<?php

namespace Pinimize\Facades;

use Closure;
use Illuminate\Support\Facades\Facade;
use Pinimize\Contracts\DecompressionContract;
use Pinimize\Managers\DecompressionManager;

/**
 * @method static DecompressionManager driver($driver = null)
 * @method static DecompressionManager extend($driver, Closure $callback)
 *
 * @mixin DecompressionContract
 *
 * @see DecompressionManager
 */
class Decompression extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'pinimize.decompression';
    }
}
