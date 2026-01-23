<?php

declare(strict_types=1);

namespace InfoEsportes\Messaging\Exceptions;

class PublishException extends \Exception
{
    public function __construct(string $message = 'Failed to publish message', int $code = 0, ?\Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
