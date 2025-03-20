<?php

namespace App\Webhook;

use Symfony\Component\HttpFoundation\ChainRequestMatcher;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher\IsJsonRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcher\MethodRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Symfony\Component\Webhook\Client\AbstractRequestParser;
use Symfony\Component\Webhook\Exception\RejectWebhookException;

final class BoxtalRequestParser extends AbstractRequestParser
{
    private string $secret;

    public function __construct(string $secret)
    {
        $this->secret = $secret;
    }

    protected function getRequestMatcher(): RequestMatcherInterface
    {
        return new ChainRequestMatcher([
            new IsJsonRequestMatcher(),
            new MethodRequestMatcher('POST'),
        ]);
    }

    /**
     * @throws JsonException
     */
    protected function doParse(Request $request, #[\SensitiveParameter] string $secret): ?RemoteEvent
    {
        $payload = $this->verifyBoxtalSignature($request);

        // Validate the request payload.
        if (!$request->getPayload()->has('type')
            || !$request->getPayload()->has('shippingOrderId')) {
            throw new RejectWebhookException(Response::HTTP_BAD_REQUEST, 'Request payload does not contain required fields.');
        }

        // Parse the request payload and return a RemoteEvent object.
        $payload = $request->getPayload();

        return new RemoteEvent(
            $payload->getString('type'),
            $payload->getString('shippingOrderId'),
            $payload->all(),
        );
    }

    private function verifyBoxtalSignature(Request $request): array
    {
        $rawPayload = $request->getContent();
        $payload = json_decode($rawPayload, true);
        $secret = $this->secret;

        $signature = hash_hmac('sha256', $rawPayload, $secret);

        if ($signature !== $request->headers->get('x-bxt-signature')) {
            throw new RejectWebhookException(Response::HTTP_BAD_REQUEST, 'Invalid signature.');
        }

        return $payload;
    }
}
