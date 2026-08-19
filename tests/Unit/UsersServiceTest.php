<?php

namespace YPost\DummyJsonUsers\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use YPost\DummyJsonUsers\Exception\RemoteApiException;
use YPost\DummyJsonUsers\Exception\UserNotFoundException;
use YPost\DummyJsonUsers\UsersService;

#[CoversClass(UsersService::class)]
class UsersServiceTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function testFetchesUser(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'id' => 1,
                'firstName' => 'John',
                'lastName' => 'Doe',
                'email' => 'john@example.com',
            ], JSON_THROW_ON_ERROR)),
        ]);

        $service = new UsersService(new Client(['handler' => $mock]));
        $user = $service->getUser(1);

        self::assertSame(1, $user->id);
        self::assertSame('John', $user->firstName);
        self::assertSame('Doe', $user->lastName);
        self::assertSame('john@example.com', $user->email);
    }

    public function testThrowsUserNotFoundException(): void
    {
        $mock = new MockHandler([new Response(404)]);
        $service = new UsersService(new Client(['handler' => $mock]));
        $this->expectException(UserNotFoundException::class);
        $service->getUser(1_000_000);
    }

    public function testThrowsRemoteApiException(): void
    {
        $mock = new MockHandler([new Response(500)]);
        $service = new UsersService(new Client(['handler' => $mock]));
        $this->expectException(RemoteApiException::class);
        $service->getUser(1);
    }

    /**
     * @throws JsonException
     */
    public function testFetchesUsersList(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'users' => [
                    [
                        'id' => 1,
                        'firstName' => 'John',
                        'lastName' => 'Doe',
                        'email' => 'john@example.com',
                    ],
                ],
                'total' => 10,
                'limit' => 1,
                'skip' => 0,
            ], JSON_THROW_ON_ERROR)),
        ]);

        $service = new UsersService(new Client(['handler' => $mock]));
        $list = $service->getUsers(1);

        self::assertCount(1, $list->users);
        self::assertSame(10, $list->total);
        self::assertSame(1, $list->limit);
        self::assertSame(0, $list->offset);

        self::assertSame(1, $list->users[0]->id);
        self::assertSame('John', $list->users[0]->firstName);
        self::assertSame('Doe', $list->users[0]->lastName);
        self::assertSame('john@example.com', $list->users[0]->email);
    }
}
