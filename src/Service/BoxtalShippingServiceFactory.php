<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class BoxtalShippingServiceFactory
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $webhookSecret
    ) {
    }

    public function createForUser(string $apiKey, string $apiSecret): BoxtalShippingService
    {
        return new BoxtalShippingService(
            $apiKey,
            $apiSecret,
            $this->webhookSecret,
            $this->httpClient
        );
    }
}
