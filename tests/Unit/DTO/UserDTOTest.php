<?php

namespace YPost\DummyJsonUsers\Tests\Unit\DTO;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use YPost\DummyJsonUsers\DTO\UserDTO;

#[CoversClass(UserDTO::class)]
class UserDTOTest extends TestCase
{
    public function testConvertsToArray(): void
    {
        $user = new UserDTO(
            id: 1,
            firstName: 'John',
            lastName: 'Doe',
            email: 'john@example.com'
        );

        self::assertSame([
            'id' => 1,
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john@example.com'
        ], $user->toArray());
    }

    public function testJsonSerializable(): void
    {
        $user = new UserDTO(
            id: 1,
            firstName: 'John',
            lastName: 'Doe',
            email: 'john@example.com'
        );

        self::assertSame($user->toArray(), $user->jsonSerialize());
    }
}
