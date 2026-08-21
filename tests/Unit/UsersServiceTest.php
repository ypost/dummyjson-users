<?php

namespace YPost\DummyJsonUsers\Tests\Unit;

use GuzzleHttp\Psr7\HttpFactory;
use InvalidArgumentException;
use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use YPost\DummyJsonUsers\Exception\RemoteApiException;
use YPost\DummyJsonUsers\Exception\UserNotFoundException;
use YPost\DummyJsonUsers\Exception\UsersInvalidArgumentException;
use YPost\DummyJsonUsers\Tests\Support\MockHttpClient;
use YPost\DummyJsonUsers\UsersService;

#[CoversClass(UsersService::class)]
class UsersServiceTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function testFetchesUser(): void
    {
        $mock = new MockHttpClient(
            200,
            [
                'id' => 1,
                'firstName' => 'John',
                'lastName' => 'Doe',
                'email' => 'john@example.com',
            ]
        );

        $service = $this->createService($mock);
        $user = $service->getUser(1);

        self::assertSame(1, $user->id);
        self::assertSame('John', $user->firstName);
        self::assertSame('Doe', $user->lastName);
        self::assertSame('john@example.com', $user->email);
    }

    /**
     * @throws JsonException
     */
    public function testFetchesUsersList(): void
    {
        $mock = new MockHttpClient(
            200,
            [
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
            ]
        );

        $service = $this->createService($mock);
        $list = $service->getUsers(1);

        self::assertNotNull($mock->lastRequest);

        parse_str($mock->lastRequest->getUri()->getQuery(), $query);

        self::assertSame('1', $query['limit']);
        self::assertSame('0', $query['skip']);
        self::assertSame(UsersService::USER_FIELDS, $query['select']);

        self::assertCount(1, $list->users);
        self::assertSame(10, $list->total);
        self::assertSame(1, $list->limit);
        self::assertSame(0, $list->skip);

        self::assertSame(1, $list->users[0]->id);
        self::assertSame('John', $list->users[0]->firstName);
        self::assertSame('Doe', $list->users[0]->lastName);
        self::assertSame('john@example.com', $list->users[0]->email);
    }

    /**
     * @throws JsonException
     */
    public function testFetchesUsersPage(): void
    {
        $mock = new MockHttpClient(
            200,
            [
                'users' => [],
                'total' => 30,
                'limit' => 5,
                'skip' => 10,
            ]
        );

        $service = $this->createService($mock);
        $list = $service->getUsersPage(3, 5);

        self::assertSame(5, $list->limit);
        self::assertSame(10, $list->skip);
    }

    /**
     * @throws JsonException
     */
    public function testCreatesUser(): void
    {
        $mock = new MockHttpClient(
            201,
            [
                'id' => 1000,
                'firstName' => 'John',
                'lastName' => 'Doe',
                'email' => 'john@example.com',
            ]
        );

        $service = $this->createService($mock);
        $newUserId = $service->addUser('John', 'Doe', 'john@example.com');

        self::assertSame(1000, $newUserId);
    }

    /**
     * @throws JsonException
     */
    public function testThrowsUserNotFoundException(): void
    {
        $mock = new MockHttpClient(404, []);
        $service = $this->createService($mock);
        $this->expectException(UserNotFoundException::class);
        $service->getUser(1_000_000);
    }

    /**
     * @throws JsonException
     */
    public function testThrowsRemoteApiException(): void
    {
        $mock = new MockHttpClient(500, []);
        try {
            $service = $this->createService($mock);
            $service->getUser(1);
            self::fail('RemoteApiException should have been thrown');
        } catch (RemoteApiException $e) {
            self::assertSame(500, $e->httpStatusCode);
            self::assertSame(500, $e->getCode());
        }
    }

    /**
     * @throws JsonException
     */
    public function testThrowsUsersInvalidArgumentExceptionOnIncorrectUserId(): void
    {
        $mock = new MockHttpClient(400, []);
        $service = $this->createService($mock);
        $this->expectException(UsersInvalidArgumentException::class);
        $service->getUser(0);
    }

    /**
     * @throws JsonException
     */
    public function testThrowsUsersInvalidArgumentExceptionOnIncorrectUsersLimit(): void
    {
        $mock = new MockHttpClient(400, []);
        $service = $this->createService($mock);
        $this->expectException(UsersInvalidArgumentException::class);
        $service->getUsers(0);
    }

    /**
     * @throws JsonException
     */
    public function testThrowsUsersInvalidArgumentExceptionOnIncorrectUsersOffset(): void
    {
        $mock = new MockHttpClient(400, []);
        $service = $this->createService($mock);
        $this->expectException(UsersInvalidArgumentException::class);
        $service->getUsers(1, -1);
    }

    /**
     * @throws JsonException
     */
    public function testThrowsUsersInvalidArgumentExceptionOnIncorrectPageNumber(): void
    {
        $mock = new MockHttpClient(400, []);
        $service = $this->createService($mock);
        $this->expectException(UsersInvalidArgumentException::class);
        $service->getUsersPage(0);
    }

    /**
     * @throws JsonException
     */
    public function testThrowsUsersInvalidArgumentExceptionIfPageNumberIsTooBig(): void
    {
        $mock = new MockHttpClient(400, []);
        $service = $this->createService($mock);
        $this->expectException(UsersInvalidArgumentException::class);
        $service->getUsersPage(PHP_INT_MAX);
    }

    /**
     * @throws JsonException
     */
    public function testThrowsUsersInvalidArgumentExceptionOnIncorrectPerPageAmount(): void
    {
        $mock = new MockHttpClient(400, []);
        $service = $this->createService($mock);
        $this->expectException(UsersInvalidArgumentException::class);
        $service->getUsersPage(1, 0);
    }

    private function createService(MockHttpClient $mock): UsersService
    {
        $httpFactory = new HttpFactory();
        return new UsersService($mock, $httpFactory, $httpFactory);
    }
}
