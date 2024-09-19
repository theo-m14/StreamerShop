<?php

namespace App\Service;

interface PaymentApiClient
{
    public function getSubscription(string $subscriptionId): array;
}