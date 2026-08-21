<?php

namespace YPost\DummyJsonUsers\Tests;

use GuzzleHttp\Psr7\Response;
use JsonException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class MockHttpClient implements ClientInterface
{
    private ResponseInterface $response;

    /**
     * @param array<array-key, mixed> $responseData
     * @throws JsonException
     */
    public function __construct(int $httpStatusCode = 200, array $responseData = [])
    {
        $json = json_encode($responseData, JSON_THROW_ON_ERROR);
        $this->response = new Response($httpStatusCode, [], $json);
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        return $this->response;
    }
}
