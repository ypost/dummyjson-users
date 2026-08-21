<?php

namespace YPost\DummyJsonUsers\Exception;

use Throwable;

class RemoteApiException extends \RuntimeException implements UsersException
{

    public function __construct(
        string                  $message,
        public readonly ?int    $httpStatusCode = null,
        public readonly ?string $responseBody = null,
        ?Throwable              $previous = null
    )
    {
        parent::__construct($message, $this->httpStatusCode ?? 0, $previous);
    }
}
