<?php

namespace YPost\DummyJsonUsers\Tests\Unit\Mapper;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use YPost\DummyJsonUsers\Exception\InvalidApiResponseException;
use YPost\DummyJsonUsers\Mapper\UserMapper;

#[CoversClass(UserMapper::class)]
class UserMapperTest extends TestCase
{
    public function testMapsValidPayload(): void
    {
        $user = UserMapper::fromArray([
            'id' => 1,
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john@example.com',
        ]);

        self::assertSame(1, $user->id);
        self::assertSame('John', $user->firstName);
        self::assertSame('Doe', $user->lastName);
        self::assertSame('john@example.com', $user->email);
    }

    public function testRejectsInvalidFieldType(): void
    {
        $this->expectException(InvalidApiResponseException::class);

        $user = UserMapper::fromArray([
            'id' => '1',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john@example.com',
        ]);
    }

    public function testRejectsMissingField(): void
    {
        $this->expectException(InvalidApiResponseException::class);

        UserMapper::fromArray([
            'id' => 1,
            'firstName' => 'John',
        ]);
    }
}
