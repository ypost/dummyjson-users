<?php

namespace YPost\DummyJsonUsers\DTO;

use JsonSerializable;

/**
 * Represents a single user
 */
class UserDTO implements JsonSerializable
{
    public function __construct(
        public int    $id,
        public string $firstName,
        public string $lastName,
        public string $email,
    )
    {
    }

    /**
     * @return array{
     *     id: int,
     *     firstName: string,
     *     lastName: string,
     *     email: string,
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'email' => $this->email,
        ];
    }

    /**
     * @return array{
     *     id: int,
     *     firstName: string,
     *     lastName: string,
     *     email: string,
     * }
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
