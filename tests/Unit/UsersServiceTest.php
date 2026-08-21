<?php

namespace YPost\DummyJsonUsers\Tests\Unit;

use GuzzleHttp\Psr7\HttpFactory;
use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use YPost\DummyJsonUsers\Exception\InvalidApiResponseException;
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

        self::assertNotNull($mock->lastRequest);
        self::assertSame('GET', $mock->lastRequest->getMethod());
        self::assertSame('/users/1', $mock->lastRequest->getUri()->getPath());
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
        self::assertSame('GET', $mock->lastRequest->getMethod());
        self::assertSame('/users', $mock->lastRequest->getUri()->getPath());

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

        self::assertNotNull($mock->lastRequest);
        self::assertSame('POST', $mock->lastRequest->getMethod());
        self::assertSame('/users/add', $mock->lastRequest->getUri()->getPath());
        self::assertSame('', $mock->lastRequest->getUri()->getQuery());
        self::assertSame(
            'application/json',
            $mock->lastRequest->getHeaderLine('Content-Type')
        );

        self::assertSame(
            [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'email' => 'john@example.com',
            ],
            json_decode(
                (string)$mock->lastRequest->getBody(),
                true,
                512,
                JSON_THROW_ON_ERROR,
            ),
        );
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

    private function createService(MockHttpClient $mock, int $maxAttempts = 1): UsersService
    {
        $httpFactory = new HttpFactory();
        return new UsersService($mock, $httpFactory, $httpFactory, $maxAttempts, [0]);
    }

    /**
     * @throws JsonException
     */
    public function testRetriesFetchUserOnRetriableStatuses(): void
    {
        $mock = new MockHttpClient(500, []);
        $mock->queueResponse(502);
        $mock->queueResponse(
            200,
            [
                'id' => 1,
                'firstName' => 'John',
                'lastName' => 'Doe',
                'email' => 'john@example.com',
            ]
        );
        $service = $this->createService($mock, 3);
        $user = $service->getUser(1);

        self::assertSame(1, $user->id);
        self::assertCount(3, $mock->requests);
    }

    /**
     * @throws JsonException
     */
    public function testRetriesFetchUsersListOnRetriableStatuses(): void
    {
        $mock = new MockHttpClient(429, []);
        $mock->queueResponse(503);
        $mock->queueResponse(504);
        $mock->queueResponse(
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
        $service = $this->createService($mock, 4);
        $list = $service->getUsers(1);

        self::assertCount(1, $list->users);
        self::assertCount(4, $mock->requests);
        self::assertSame(1, $list->users[0]->id);
    }

    /**
     * @throws JsonException
     */
    public function testThrowsExceptionIfAttemptsExhausted(): void
    {
        $mock = new MockHttpClient(500, []);
        $mock->queueResponse(502);
        $mock->queueResponse();
        $service = $this->createService($mock, 2);
        self::expectException(RemoteApiException::class);
        $service->getUser(1);
    }

    /**
     * @throws JsonException
     */
    public function testAcceptsAny2xxStatusWhenFetchesUser(): void
    {
        $mock = new MockHttpClient(
            203,
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
        self::assertCount(1, $mock->requests);
    }

    /**
     * @throws JsonException
     */
    public function testAcceptsAny2xxStatusWhenAddsUser(): void
    {
        $mock = new MockHttpClient(
            200,
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
        self::assertCount(1, $mock->requests);
    }

    /**
     * @throws JsonException
     */
    public function testDoesNotRetryAddingUser(): void
    {
        $mock = new MockHttpClient(
            500,
            [
                'id' => 1000,
                'firstName' => 'John',
                'lastName' => 'Doe',
                'email' => 'john@example.com',
            ]
        );

        $service = $this->createService($mock);

        try {
            $service->addUser('John', 'Doe', 'john@example.com');
            self::fail('RemoteApiException should have been thrown');
        } catch (RemoteApiException $e) {
            self::assertCount(1, $mock->requests);
        }
    }

    /**
     * @throws JsonException
     */
    public function testDoesNotRetryNonRetriableStatus(): void
    {
        $mock = new MockHttpClient(404, []);
        $mock->queueResponse();
        $service = $this->createService($mock, 2);
        self::expectException(UserNotFoundException::class);
        $service->getUser(1);
    }

    /**
     * @throws JsonException
     */
    public function testThrowsExceptionWhenReturnedUserDataDoesNotMatch(): void
    {
        $mock = new MockHttpClient(
            200,
            [
                'id' => 1000,
                'firstName' => 'John',
                'lastName' => 'Doe',
                'email' => 'john@example.com',
            ]
        );

        $service = $this->createService($mock);
        self::expectException(InvalidApiResponseException::class);
        $service->addUser('Ada', 'Lovelace', 'first@1843.com');
    }
}
