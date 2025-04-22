<?php

namespace App\Exception;

class EntityNotFoundException extends \Exception
{
    public function __construct(string $message = "Requested entity not found", int $code = 404, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
