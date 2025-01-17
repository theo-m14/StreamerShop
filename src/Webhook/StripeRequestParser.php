<?php

namespace App\Webhook;

use Exception;
use Stripe\Stripe;
use Stripe\Webhook;
use Stripe\StripeClient;
use UnexpectedValueException;
use App\Service\PaymentApiClient;
use App\Repository\PlanRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Stripe\Exception\SignatureVerificationException;
use Symfony\Component\HttpFoundation\ChainRequestMatcher;
use Symfony\Component\Webhook\Client\AbstractRequestParser;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\Webhook\Exception\RejectWebhookException;
use Symfony\Component\HttpFoundation\RequestMatcher\HostRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcher\PathRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcher\IsJsonRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcher\MethodRequestMatcher;

final class StripeRequestParser extends AbstractRequestParser
{
    private string $apiKey;
    private string $endpointSecret;
    private PaymentApiClient $paymentApiClient;

    public function __construct(string $apiKey, string $endpointSecret, private readonly UserRepository $userRepository, private readonly PlanRepository $planRepository, PaymentApiClient $paymentApiClient)
    {
        $this->apiKey = $apiKey;
        $this->endpointSecret = $endpointSecret;
        $this->paymentApiClient = $paymentApiClient;
        
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
        //Stripe connection setup
        $event = $this->verifyStripeSignature($request);

        //Stripe event handling
        switch ($event['type']) {
            case 'checkout.session.completed':
                $checkoutSession = $event['data']['object'];
                return new RemoteEvent('checkout.session.completed', $checkoutSession['id'], [
                    'email' => $checkoutSession['customer_email'],
                    'statut' => $checkoutSession['payment_status'],
                ]);
                break;
            case 'checkout.session.async_payment_succeeded':
                $checkoutSession = $event['data']['object'];
                return new RemoteEvent('checkout.session.async_payment_succeeded', $checkoutSession['id'], [
                    'email' => $checkoutSession['customer_email'],
                ]);
                break;
            case 'checkout.session.async_payment_failed':
                $checkoutSession = $event['data']['object'];
                return new RemoteEvent('checkout.session.async_payment_failed', $checkoutSession['id'], [
                    'email' => $checkoutSession['customer_email'],
                ]);
                break;
            default:
                //Invalid event type
                throw new RejectWebhookException(Response::HTTP_BAD_REQUEST, 'Invalid event type.');
        }

    }

    private function verifyStripeSignature(Request $request): array
    {
        Stripe::setApiKey($this->apiKey);
        $stripeSignature = $request->headers->get('Stripe-Signature');
        $endpointSecret = $this->endpointSecret;
        $payload = $request->getContent();

        //Stripe event verification
        try {
            $event = Webhook::constructEvent($payload, $stripeSignature, $endpointSecret);
        } catch (UnexpectedValueException $e) {
            throw new RejectWebhookException(Response::HTTP_BAD_REQUEST, 'Invalid payload.');
        } catch (SignatureVerificationException $e) {
            throw new RejectWebhookException(Response::HTTP_BAD_REQUEST, $e->getMessage());
        }

        $event = $event->toArray();

        return $event;
    }
}
