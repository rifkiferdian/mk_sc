<?php

namespace App\Exceptions;

use CodeIgniter\Exceptions\HTTPExceptionInterface;
use CodeIgniter\Exceptions\RuntimeException;

class ForbiddenException extends RuntimeException implements HTTPExceptionInterface
{
    protected $code = 403;

    public static function forPermission(): self
    {
        return new self('Anda tidak memiliki izin untuk mengakses halaman ini.');
    }
}
