<?php

declare(strict_types=1);

namespace Pinimize\Exceptions;

use RuntimeException;
use Throwable;

class InvalidCompressedDataException extends RuntimeException
{
    public function __construct(string $message = 'Failed to decompress data: invalid compressed data', int $code = 0, ?Throwable $throwable = null)
    {
        parent::__construct($message, $code, $throwable);
    }
}
