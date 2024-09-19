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

                $subscriptionId = $checkoutSession['subscription'];

                $subscription = $this->paymentApiClient->getSubscription($subscriptionId);

                $planId = $subscription['plan']['id'];

                $userId = $checkoutSession['client_reference_id'];

                $user = $this->userRepository->find($userId);

                //User verification
                if(!$user) {
                    throw new RejectWebhookException(Response::HTTP_BAD_REQUEST, 'User not found.');
                }

                //Plan verification
                $plan = $this->planRepository->findOneBy(['stripeId' => $planId]);

                if(!$plan) {
                    throw new RejectWebhookException(Response::HTTP_BAD_REQUEST, 'Plan not found.');
                }

                //Remote event creation
                return new RemoteEvent('checkout.session.completed', $subscriptionId, [
                    'user' => $user,
                    'plan' => $plan,
                    'user_stripe_id' => $checkoutSession['customer'],
                    'current_period_start' => $subscription['current_period_start'],
                    'current_period_end' => $subscription['current_period_end'],
                ]);
                break;
            case 'invoice.paid':
                $subscriptionId = $event['data']['object']['subscription'];

                //Subscription verification
                if(!$subscriptionId) {
                    throw new RejectWebhookException(Response::HTTP_BAD_REQUEST, 'Subscription not found.');
                }

                //Remote event creation
                return new RemoteEvent('invoice.paid', $subscriptionId, [
                    'id' => $event['data']['object']['id'],
                    'number' => $event['data']['object']['number'],
                    'amount_paid' => $event['data']['object']['amount_paid'],
                    'hosted_invoice_url' => $event['data']['object']['hosted_invoice_url'],
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
