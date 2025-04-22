<?php

namespace App\Exception;

class InvalidAccountTypeException extends \Exception
{
    public function __construct(string $message = "Invalid account type", int $code = 400, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
