<?php

namespace YPost\DummyJsonUsers\Mapper;

use YPost\DummyJsonUsers\DTO\UserDTO;
use YPost\DummyJsonUsers\Exception\InvalidApiResponseException;

class UserMapper
{
    /**
     * @param array<array-key, mixed> $data
     */
    public static function fromArray(array $data): UserDTO
    {
        if (!isset($data['id'], $data['firstName'], $data['lastName'], $data['email'])
            || !is_int($data['id']) || !is_string($data['firstName']) || !is_string($data['lastName']) || !is_string(
                $data['email']
            )
        ) {
            throw new InvalidApiResponseException('API has returned invalid response data');
        }

        return new UserDTO(
            id: $data['id'],
            firstName: $data['firstName'],
            lastName: $data['lastName'],
            email: $data['email'],
        );
    }
}
