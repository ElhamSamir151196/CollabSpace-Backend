<?php

namespace App\Exceptions;

/**
 * The AI could not answer (turned off, misconfigured, provider down ...).
 * Shown to the user as a clean 503 message; details go to the log.
 */
class AiUnavailableException extends BusinessException
{
    public function __construct(string $message = '')
    {
        parent::__construct($message !== '' ? $message : __('The AI assistant is not available right now. Please try again later.'), 503);
    }

    public static function disabled(): self
    {
        return new self(__('AI features are turned off by the administrator.'));
    }

    public static function moduleDisabled(string $module): self
    {
        return new self(__('The AI is not allowed to read :module data.', ['module' => $module]));
    }
}
