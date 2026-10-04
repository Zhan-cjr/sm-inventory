<?php

namespace App\Exceptions;

use Exception;

class PpobFailedException extends Exception
{
    protected array $failedItem;

    public function __construct(string $message, array $failedItem = [], int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->failedItem = $failedItem;
    }

    public function getFailedItem(): array
    {
        return $this->failedItem;
    }
}
