<?php

namespace YPost\DummyJsonUsers\Exception;

class UserNotFoundException extends \RuntimeException implements UsersException
{
    public function __construct(
        public readonly int $id
    )
    {
        parent::__construct(sprintf('User %d not found.', $id));
    }
}
