<?php

namespace YPost\DummyJsonUsers\Tests\Support;

use GuzzleHttp\Psr7\Message;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class DebugHttpClient implements ClientInterface
{
    private(set) ?string $lastRawRequest = null;
    private(set) ?string $lastRawResponse = null;

    private ?RequestInterface $lastRequest = null;

    public ?string $lastUrl {
        get => $this->lastRequest === null ? null : (string)$this->lastRequest->getUri();
    }

    public function __construct(
        private readonly ClientInterface $client,
    ) {
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->lastRequest = $request;
        $this->lastRawRequest = Message::toString($request);
        Message::rewindBody($request);

        $response = $this->client->sendRequest($request);
        $this->lastRawResponse = Message::toString($response);
        Message::rewindBody($response);

        return $response;
    }
}
