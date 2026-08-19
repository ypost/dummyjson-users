<?php

namespace YPost\DummyJsonUsers\Exception;

use YPost\DummyJsonUsers\Exception\UsersException;

class UserNotFoundException extends UsersException
{
    public function __construct(
        public readonly int $id
    )
    {
        parent::__construct(sprintf('User %d not found.', $id));
    }
}
