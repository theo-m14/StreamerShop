<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class BoxtalShippingService
{
    public function __construct(
        private readonly string $boxtalApiKey,    // Spécifique à l'utilisateur
        private readonly string $boxtalApiSecret, // Spécifique à l'utilisateur
        private readonly string $secret,          // Application-wide
        private readonly HttpClientInterface $httpClient
    ) {
    }

    public function getProductCategories(): array
    {
        $response = $this->httpClient->request('GET', 'https://api.boxtal.build/shipping/v3.1/content-category?language=fr', [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($this->boxtalApiKey . ':' . $this->boxtalApiSecret),
            ],
        ]);
        $content = $response->toArray();
        return $content;
    }

    public function createShipment(array $shipment): ResponseInterface
    {

        $response = $this->httpClient->request('POST', 'https://api.boxtal.build/shipping/v3.1/shipping-order', [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($this->boxtalApiKey . ':' . $this->boxtalApiSecret),
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($shipment),
        ]);

        return $response;
    }

    public function createSubscription(string $eventType): ResponseInterface
    {
        $subscription = [
            "eventType" => $eventType,
            //On génère l'url du webhook handler en absolu
            "callbackUrl" => "https://a737-2001-861-52d0-71f0-b57d-de7-454a-ce9a.ngrok-free.app/webhook/boxtal",
            "webhookSecret" => $this->secret
        ];


        $response = $this->httpClient->request('POST', 'https://api.boxtal.build/shipping/v3.1/subscription', [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($this->boxtalApiKey . ':' . $this->boxtalApiSecret),
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($subscription),
        ]);

        return $response;

    }

    public function getShipmentDocument(string $shipmentId): array

    {
        $response = $this->httpClient->request('GET', 'https://api.boxtal.build/shipping/v3.1/shipping-order/' . $shipmentId . '/shipping-document',
            [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($this->boxtalApiKey . ':' . $this->boxtalApiSecret),
                ],
            ]
        );
        $content = $response->toArray();
        return $content;
    }

    public function getSubscription(): array
    {
        $response = $this->httpClient->request('GET', 'https://api.boxtal.build/shipping/v3.1/subscription', [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($this->boxtalApiKey . ':' . $this->boxtalApiSecret),
            ],
        ]);
        $content = $response->toArray();
        return $content;
    }

    public function deleteSubscription(string $id): array
    {
        $response = $this->httpClient->request('DELETE', 'https://api.boxtal.build/shipping/v3.1/subscription/' . $id, [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($this->boxtalApiKey . ':' . $this->boxtalApiSecret),
            ],
        ]);
        $content = $response->toArray();
        return $content;
    }
}

