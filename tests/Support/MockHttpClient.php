<?php

namespace YPost\DummyJsonUsers\Tests\Support;

use GuzzleHttp\Psr7\Response;
use JsonException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class MockHttpClient implements ClientInterface
{
    /** @var list<ResponseInterface> */
    private array $responseQueue = [];

    /** @var list<RequestInterface> */
    private(set) array $requests = [];

    /** @var list<ResponseInterface> */
    private(set) array $responses = [];

    public ?RequestInterface $lastRequest {
        get => empty($this->requests) ? null : $this->requests[count($this->requests) - 1];
    }

    public ?ResponseInterface $lastResponse {
        get => empty($this->responses) ? null : $this->responses[count($this->responses) - 1];
    }

    /**
     * @param array<array-key, mixed> $responseData
     * @throws JsonException
     */
    public function __construct(int $httpStatusCode = 200, array $responseData = [])
    {
        $this->queueResponse($httpStatusCode, $responseData);
    }

    /**
     * @param array<array-key, mixed> $responseData
     * @throws JsonException
     */
    public function queueResponse(int $httpStatusCode = 200, array $responseData = []): void
    {
        $json = json_encode($responseData, JSON_THROW_ON_ERROR);
        $this->responseQueue[] = new Response($httpStatusCode, [], $json);
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->requests[] = $request;

        $response = array_shift($this->responseQueue);

        if ($response === null) {
            $response = $this->lastResponse;
        } else {
            $this->responses[] = $response;
        }

        if ($response === null) {
            throw new RuntimeException('No response available for the request');
        }

        return $response;
    }
}
