<?php

namespace YPost\DummyJsonUsers;

use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
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
    private const string DEFAULT_BASE_URI = "https://dummyjson.com";

    public function __construct(
        private readonly ClientInterface         $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface  $streamFactory,
        private readonly string                  $baseUri = self::DEFAULT_BASE_URI,
    )
    {
    }

    public function getUser(int $id): UserDTO
    {
        if ($id < 1) {
            throw new UsersInvalidArgumentException('Invalid user ID: must be a positive number');
        }

        try {
            $url = sprintf('%s/users/%d', $this->baseUri, $id);
            $request = $this->requestFactory->createRequest('GET', $url);
            $response = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new RemoteApiException(
                message: 'Failed to get user from remote API',
                previous: $e,
            );
        }

        if ($response->getStatusCode() === 404) {
            throw new UserNotFoundException($id);
        }

        if ($response->getStatusCode() !== 200) {
            $statusCode = $response->getStatusCode();
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

        try {
            $url = sprintf('%s/users', $this->baseUri);
            $request = $this->requestFactory->createRequest('GET', $url);

            $query = [
                'limit' => $limit,
                'skip' => $skip,
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

            $response = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new RemoteApiException(
                message: 'Failed to get users list from remote API',
                previous: $e,
            );
        }

        if ($response->getStatusCode() !== 200) {
            $statusCode = $response->getStatusCode();
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

        if ($response->getStatusCode() !== 201) {
            $statusCode = $response->getStatusCode();
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
}
