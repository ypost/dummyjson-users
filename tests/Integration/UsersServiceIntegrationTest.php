<?php

namespace YPost\DummyJsonUsers\Tests\Integration;

use GuzzleHttp\Client;
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
        $this->service = new UsersService(new Client([RequestOptions::TIMEOUT => 10.0]));
    }

    public function testFetchesUser(): void
    {
        $user = $this->service->getUser(1);

        self::assertSame(1, $user->id);
        self::assertNotSame('', $user->firstName);
        self::assertNotSame('', $user->lastName);
        self::assertNotSame('', $user->email);
    }
}
