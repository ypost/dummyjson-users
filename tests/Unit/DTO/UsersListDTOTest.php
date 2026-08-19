<?php

namespace YPost\DummyJsonUsers\Tests\Unit\DTO;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use YPost\DummyJsonUsers\DTO\UserDTO;
use YPost\DummyJsonUsers\DTO\UsersListDTO;

#[CoversClass(UsersListDTO::class)]
class UsersListDTOTest extends TestCase
{
    public function testConvertsToArray(): void
    {
        $usersList = new UsersListDTO(
            users: [
                new UserDTO(
                    id: 1,
                    firstName: 'John',
                    lastName: 'Doe',
                    email: 'john@example.com'
                ),
                new UserDTO(
                    id: 2,
                    firstName: 'Agent',
                    lastName: 'Smith',
                    email: 'escape@matrix.com'
                ),
            ],
            total: 10,
            limit: 2,
            offset: 0,
        );

        self::assertSame([
            'users' => [
                [
                    'id' => 1,
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                    'email' => 'john@example.com'
                ],
                [
                    'id' => 2,
                    'firstName' => 'Agent',
                    'lastName' => 'Smith',
                    'email' => 'escape@matrix.com'
                ],
            ],
            'total' => 10,
            'limit' => 2,
            'offset' => 0,
        ], $usersList->toArray());
    }

    public function testJsonSerializable(): void
    {
        $usersList = new UsersListDTO(
            users: [
                new UserDTO(
                    id: 1,
                    firstName: 'John',
                    lastName: 'Doe',
                    email: 'john@example.com'
                ),
            ],
            total: 10,
            limit: 1,
            offset: 0,
        );

        self::assertSame($usersList->toArray(), $usersList->jsonSerialize());
    }
}
