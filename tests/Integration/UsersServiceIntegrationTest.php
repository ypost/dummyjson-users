<?php

namespace YPost\DummyJsonUsers\Tests\Integration;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use YPost\DummyJsonUsers\UsersService;

#[CoversClass(UsersService::class)]
class UsersServiceIntegrationTest extends TestCase
{
    private UsersService $service;

    #[\Override]
    protected function setUp(): void
    {
        $client = new Client(['timeout' => 1.0]);
        $httpFactory = new HttpFactory();
        $this->service = new UsersService($client, $httpFactory, $httpFactory);
    }

    public function testFetchesUser(): void
    {
        $user = $this->service->getUser(1);

        self::assertSame(1, $user->id);
        self::assertNotSame('', $user->firstName);
        self::assertNotSame('', $user->lastName);
        self::assertNotSame('', $user->email);
    }

    public function fetchesUsersList(): void
    {
        $list = $this->service->getUsers(5, 10);

        self::assertCount(5, $list->users);
        self::assertSame(5, $list->limit);
        self::assertSame(10, $list->skip);

        self::assertNotSame('', $list->users[0]->firstName);
        self::assertNotSame('', $list->users[0]->lastName);
        self::assertNotSame('', $list->users[0]->email);
    }


    public function testAddsUser(): void
    {
        $newUserId = $this->service->addUser('John', 'Doe', 'john@example.com');

        self::assertGreaterThan(0, $newUserId);
    }
}
