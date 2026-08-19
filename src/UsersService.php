<?php

namespace YPost\DummyJsonUsers;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use JsonException;
use YPost\DummyJsonUsers\DTO\UserDTO;
use YPost\DummyJsonUsers\DTO\UsersListDTO;
use YPost\DummyJsonUsers\Exception\InvalidApiResponseException;
use YPost\DummyJsonUsers\Exception\RemoteApiException;
use YPost\DummyJsonUsers\Exception\UserNotFoundException;
use YPost\DummyJsonUsers\Mapper\UserMapper;

class UsersService
{
    private const string DEFAULT_BASE_URI = "https://dummyjson.com";

    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly string $baseUri = self::DEFAULT_BASE_URI,
        private readonly float $timeout = 10.0,
    )
    {
    }

    public function getUser(int $id): UserDTO
    {
        try {
            $url = sprintf('%s/users/%d', $this->baseUri, $id);
            $options = [
                RequestOptions::HTTP_ERRORS => false,
                RequestOptions::TIMEOUT => $this->timeout,
            ];

            $response = $this->httpClient->request('GET', $url, $options);
        } catch (GuzzleException $e) {
            throw new RemoteApiException('Failed to get user from remote API', 0, $e);
        }

        if ($response->getStatusCode() === 404) {
            throw new UserNotFoundException($id);
        }

        if ($response->getStatusCode() !== 200) {
            throw new RemoteApiException(sprintf('Failed to get user from remote API, got status code %d', $response->getStatusCode()));
        }

        $data = $this->decodeResponse($response->getBody()->getContents());

        return UserMapper::fromArray($data);
    }

    public function getUsers(int $limit = 10, int $offset = 0): UsersListDTO
    {
        try {
            $url = sprintf('%s/users', $this->baseUri);
            $options = [
                RequestOptions::HTTP_ERRORS => false,
                RequestOptions::TIMEOUT => $this->timeout,
                RequestOptions::QUERY => [
                    'limit' => $limit,
                    'skip' => $offset,
                ],
            ];

            $response = $this->httpClient->request('GET', $url, $options);
        } catch (GuzzleException $e) {
            throw new RemoteApiException('Failed to get users list from remote API', 0, $e);
        }

        if ($response->getStatusCode() !== 200) {
            throw new RemoteApiException(sprintf('Failed to get users list from remote API, got status code %d', $response->getStatusCode()));
        }

        $data = $this->decodeResponse($response->getBody()->getContents());

        if (empty($data['users']) || !is_array($data['users'])) {
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
            offset: $offset,
        );
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
