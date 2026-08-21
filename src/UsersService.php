<?php

namespace YPost\DummyJsonUsers;

use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use YPost\DummyJsonUsers\DTO\UserDTO;
use YPost\DummyJsonUsers\DTO\UsersListDTO;
use YPost\DummyJsonUsers\Exception\InvalidApiResponseException;
use YPost\DummyJsonUsers\Exception\RemoteApiException;
use YPost\DummyJsonUsers\Exception\UserNotFoundException;
use YPost\DummyJsonUsers\Exception\UsersInvalidArgumentException;
use YPost\DummyJsonUsers\Mapper\UserMapper;

class UsersService
{
    private const string DEFAULT_BASE_URI = 'https://dummyjson.com';
    public const string USER_FIELDS = 'id,firstName,lastName,email';
    private const array RETRY_STATUS_CODES = [429, 500, 502, 503, 504];

    public function __construct(
        private readonly ClientInterface         $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface  $streamFactory,
        private readonly int                     $maxAttempts = 3,
        /** @var list<int> */
        private readonly array                   $retryDelayMs = [100, 300, 500, 1000],
        private readonly string                  $baseUri = self::DEFAULT_BASE_URI,
    )
    {
        if ($this->maxAttempts < 1) {
            throw new UsersInvalidArgumentException('Maximum number of attempts should be greater than 0.');
        }

        if (empty($this->retryDelayMs)) {
            throw new UsersInvalidArgumentException('Delays list must not be empty.');
        }

        foreach ($this->retryDelayMs as $delay) {
            if ($delay < 0) {
                throw new UsersInvalidArgumentException('Delay cannot be less than zero.');
            }
        }
    }

    public function getUser(int $id): UserDTO
    {
        if ($id < 1) {
            throw new UsersInvalidArgumentException('Invalid user ID: must be a positive number');
        }

        $url = sprintf('%s/users/%d', $this->baseUri, $id);
        $request = $this->requestFactory->createRequest('GET', $url);
        $response = $this->sendWithRetry($request, 'Failed to get user from remote API');

        if ($response->getStatusCode() === 404) {
            throw new UserNotFoundException($id);
        }

        $statusCode = $response->getStatusCode();

        if (!$this->isSuccessfulStatusCode($statusCode)) {
            throw new RemoteApiException(
                sprintf('Failed to get user from remote API, got status code %d', $statusCode),
                $statusCode,
                (string)$response->getBody(),
            );
        }

        $data = $this->decodeResponse($response->getBody()->getContents());

        return UserMapper::fromArray($data);
    }

    public function getUsers(int $limit = 10, int $skip = 0): UsersListDTO
    {
        if ($limit < 1) {
            throw new UsersInvalidArgumentException('Invalid users limit: must be a positive number');
        }

        if ($skip < 0) {
            throw new UsersInvalidArgumentException('Invalid users offset: must be a positive number or zero');
        }

        $url = sprintf('%s/users', $this->baseUri);
        $request = $this->requestFactory->createRequest('GET', $url);

        $query = [
            'limit' => $limit,
            'skip' => $skip,
            'select' => self::USER_FIELDS,
        ];
        $uri = $request->getUri()->withQuery(
            http_build_query(
                $query,
                '',
                '&',
                PHP_QUERY_RFC3986,
            )
        );
        $request = $request->withUri($uri);

        $response = $this->sendWithRetry($request, 'Failed to get users list from remote API');

        $statusCode = $response->getStatusCode();

        if (!$this->isSuccessfulStatusCode($statusCode)) {
            throw new RemoteApiException(
                sprintf('Failed to get users list from remote API, got status code %d', $statusCode),
                $statusCode,
                (string)$response->getBody(),
            );
        }

        $data = $this->decodeResponse($response->getBody()->getContents());

        if (!isset($data['users']) || !is_array($data['users'])) {
            throw new InvalidApiResponseException('Failed to get users list: missing or not an array');
        }

        if (empty($data['total']) || !is_int($data['total'])) {
            throw new InvalidApiResponseException('Failed to get users list: missing total number');
        }

        $users = [];
        foreach ($data['users'] as $user) {
            if (!is_array($user)) {
                throw new InvalidApiResponseException('Failed to get users list: invalid user item encountered');
            }

            $users[] = UserMapper::fromArray($user);
        }

        return new UsersListDTO(
            users: $users,
            total: $data['total'],
            limit: $limit,
            skip: $skip,
        );
    }

    public function getUsersPage(int $page = 1, int $perPage = 10): UsersListDTO
    {
        if ($page < 1) {
            throw new UsersInvalidArgumentException('Invalid page number: must be a positive number');
        }

        if ($perPage < 1) {
            throw new UsersInvalidArgumentException('Invalid per page count: must be a positive number');
        }

        if ($page - 1 > intdiv(PHP_INT_MAX, $perPage)) {
            throw new UsersInvalidArgumentException('Invalid page number: value out of range');
        }

        return $this->getUsers(
            $perPage,
            ($page - 1) * $perPage,
        );
    }

    public function addUser(
        string $firstName,
        string $lastName,
        string $email,
    ): int
    {
        try {
            $url = sprintf('%s/users/add', $this->baseUri);
            $json = json_encode([
                'firstName' => $firstName,
                'lastName' => $lastName,
                'email' => $email,
            ], JSON_THROW_ON_ERROR);

            $request = $this->requestFactory->createRequest('POST', $url);
            $request = $request
                ->withHeader('Content-Type', 'application/json')
                ->withBody($this->streamFactory->createStream($json));

            $response = $this->httpClient->sendRequest($request);
        } catch (JsonException $e) {
            throw new UsersInvalidArgumentException('Failed to encode user data to JSON', 0, $e);
        } catch (ClientExceptionInterface $e) {
            throw new RemoteApiException(
                message: 'Failed to add user using remote API',
                previous: $e,
            );
        }

        $statusCode = $response->getStatusCode();

        if (!$this->isSuccessfulStatusCode($statusCode)) {
            throw new RemoteApiException(
                sprintf('Failed to add user using remote API, got status code %d', $statusCode),
                $statusCode,
                (string)$response->getBody(),
            );
        }

        $data = $this->decodeResponse($response->getBody()->getContents());

        if (empty($data['id'])) {
            throw new InvalidApiResponseException('Failed to add user using remote API: missing "id" field');
        }

        if (!is_int($data['id'])) {
            throw new InvalidApiResponseException('Failed to add user using remote API: id is not an integer');
        }

        if (!isset($data['firstName'], $data['lastName'], $data['email'])
            || $data['firstName'] !== $firstName
            || $data['lastName'] !== $lastName
            || $data['email'] !== $email
        ) {
            throw new InvalidApiResponseException('Failed to add user using remote API: returned user data does not match');
        }

        return $data['id'];
    }

    /** @return array<array-key, mixed> */
    private function decodeResponse(string $json): array
    {
        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidApiResponseException('Failed to decode JSON', 0, $e);
        }

        if (!is_array($data)) {
            throw new InvalidApiResponseException('Failed to decode JSON: not an array');
        }

        return $data;
    }

    private function isSuccessfulStatusCode(int $statusCode): bool
    {
        return $statusCode >= 200 && $statusCode < 300;
    }

    private function sendWithRetry(RequestInterface $request, string $errorMessage): ResponseInterface
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= $this->maxAttempts; $attempt++) {
            try {
                $response = $this->httpClient->sendRequest($request);
            } catch (ClientExceptionInterface $e) {
                if ($attempt === $this->maxAttempts) {
                    throw new RemoteApiException(
                        message: $errorMessage,
                        previous: $e,
                    );
                }

                $this->waitBeforeRetry($attempt);
                continue;
            }

            $statusCode = $response->getStatusCode();
            $shouldRetry = in_array($statusCode, self::RETRY_STATUS_CODES, true);

            if (!$shouldRetry || $attempt === $this->maxAttempts) {
                return $response;
            }

            $this->waitBeforeRetry($attempt);
        }

        throw new RemoteApiException(
            message: $errorMessage,
            previous: $lastException,
        );
    }

    private function waitBeforeRetry(int $attemptNumber): void
    {
        $delayIndex = min($attemptNumber, count($this->retryDelayMs)) - 1;
        $delayMs = $this->retryDelayMs[$delayIndex];

        if ($delayMs > 0) {
            usleep($delayMs * 1000);
        }
    }
}
