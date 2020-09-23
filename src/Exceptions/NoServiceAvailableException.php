<?php

declare(strict_types=1);

namespace Api\Gateway\Exceptions;

class NoServiceAvailableException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('No service available to handle this request');
    }
}