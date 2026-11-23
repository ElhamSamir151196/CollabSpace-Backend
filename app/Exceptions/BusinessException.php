<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown by Actions when a business rule is broken
 * ("You can't deactivate your own account", "Account is deactivated" ...).
 *
 * It is expected, so it is not reported to the logs, and the
 * ApiExceptionRenderer returns its message to the client as-is.
 */
class BusinessException extends RuntimeException
{
    public function __construct(string $message, private readonly int $status = 422)
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return $this->status;
    }

    public static function forbidden(string $message): self
    {
        return new self($message, 403);
    }

    public static function unauthorized(string $message): self
    {
        return new self($message, 401);
    }
}
