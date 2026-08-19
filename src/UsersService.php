<?php

namespace YPost\DummyJsonUsers;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use JsonException;
use YPost\DummyJsonUsers\DTO\UserDTO;
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
