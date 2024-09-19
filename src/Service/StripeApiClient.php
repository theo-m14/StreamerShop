<?php

namespace App\Service;

use Stripe\StripeClient;

class StripeApiClient implements PaymentApiClient
{
    private StripeClient $stripeClient;
    private string $apiKey;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
        $this->stripeClient = new StripeClient($this->apiKey);
    }

    public function getSubscription(string $subscriptionId): array
    {
        $subscription = $this->stripeClient->subscriptions->retrieve($subscriptionId);  

        return $subscription->toArray();
    }
}