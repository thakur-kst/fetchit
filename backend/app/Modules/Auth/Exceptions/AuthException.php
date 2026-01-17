<?php

namespace Modules\Auth\Exceptions;

use Exception;

class AuthException extends Exception
{
    public static function notFound(string $id): self
    {
        return new self("Auth with ID {$id} not found");
    }

    public static function invalidData(string $message): self
    {
        return new self("Invalid Auth data: {$message}");
    }
}
