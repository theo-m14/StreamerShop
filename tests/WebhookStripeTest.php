<?php

namespace App\Tests;

use App\Entity\Plan;
use App\Entity\User;
use App\Entity\Invoice;
use Stripe\StripeClient;
use App\Entity\Subscription;
use Stripe\WebhookSignature;
use App\Service\PaymentApiClient;
use App\Tests\Traits\TestHelpers;
use App\Webhook\StripeRequestParser;
use Stripe\Service\SubscriptionService;
use App\RemoteEvent\StripeWebhookConsumer;
use Stripe\Subscription as StripeSubcription;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class WebhookStripeTest extends WebTestCase
{
    use TestHelpers;
    private const SECRET = 'whsec_test_secret';
    private KernelBrowser $client;
    private const EVENT_PAYLOAD = '{"type":"checkout.session.completed","data":{"object":{"client_reference_id":"1","subscription":"sub_123456","customer":"cus_123456","current_period_start":"1714857600","current_period_end":"1717449600"}}}';

    protected function setUp(): void
    {
        $this->client = $this->createClient();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get('security.user_password_hasher');

        $this->initializeDependencies($entityManager, $passwordHasher);
    }

    //Generate header for webhook test with stripe signature
    private function generateHeader($opts = [])
    {
        $timestamp = \array_key_exists('timestamp', $opts) ? $opts['timestamp'] : \time();
        $payload = \array_key_exists('payload', $opts) ? $opts['payload'] : self::EVENT_PAYLOAD;
        $secret = \array_key_exists('secret', $opts) ? $opts['secret'] : self::SECRET;
        $scheme = \array_key_exists('scheme', $opts) ? $opts['scheme'] : WebhookSignature::EXPECTED_SCHEME;
        $signature = \array_key_exists('signature', $opts) ? $opts['signature'] : null;
        if (null === $signature) {
            $signedPayload = "{$timestamp}.{$payload}";
            $signature = \hash_hmac('sha256', $signedPayload, $secret);
        }

        return "t={$timestamp},{$scheme}={$signature}";
    }

   

    public function testCheckoutSessionCompleted()
    {
        // Préparer les données de test
        $user = $this->createUser();

        $plan = $this->createPlan();

        // Simuler un événement Stripe
        $payload = [
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'client_reference_id' => $user->getId(),
                    'subscription' => 'sub_123456',
                    'customer' => 'cus_123456',
                    'current_period_start' => '1714857600',
                    'current_period_end' => '1717449600',
                ]
            ]
        ];

        $stripeApiClient = $this->createMock(PaymentApiClient::class);
        // Mocker le PaymentApiClient
        $mockSubscription = [
            'plan' => ['id' => 'plan_test123'],
            'current_period_start' => 1714857600,
            'current_period_end' => 1717449600,
        ];
        $stripeApiClient->expects($this->once())
            ->method('getSubscription')
            ->willReturn($mockSubscription);
        
        $this->client->getContainer()->set(PaymentApiClient::class, $stripeApiClient);

        // Envoyer la requête webhook
        $this->client->request(
            'POST',
            '/webhook/stripe',
            [],
            [],
            ['HTTP_CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => $this->generateHeader(["payload" => json_encode($payload)])],
            json_encode($payload)
        );


        // Vérifier la réponse
        $this->assertResponseStatusCodeSame(Response::HTTP_ACCEPTED);

        // Vérifier que la subscription a été créée
        $subscription = $this->entityManager->getRepository(Subscription::class)->findOneBy(['stripeId' => 'sub_123456']);
        $this->assertNotNull($subscription);
        $this->assertTrue($subscription->isActive());
        $this->assertEquals($user, $subscription->getUser());
        $this->assertEquals($plan, $subscription->getPlan());
    }

    public function testInvoicePaid()
    {
        // Préparer les données de test
        $subscription = new Subscription();
        $subscription->setStripeId('sub_123456');
        $user = $this->createUser();
        $subscription->setUser($user);
        $plan = $this->createPlan();
        $subscription->setPlan($plan);
        $subscription->setCurrentPeriodEnd(new \DateTimeImmutable('now'));
        $subscription->setCurrentPeriodStart(new \DateTimeImmutable('now'));
        /** @var DateTimeImmutable $oldCurrentPeriodEnd */
        $oldCurrentPeriodEnd = $subscription->getCurrentPeriodEnd();
        $subscription->setActive(true);
        $this->entityManager->persist($subscription);
        $this->entityManager->flush();

        // Simuler un événement Stripe
        $payload = [
            'type' => 'invoice.paid',
            'data' => [
                'object' => [
                    'id' => 'in_123456',
                    'subscription' => 'sub_123456',
                    'number' => 'INV-001',
                    'amount_paid' => 1000,
                    'hosted_invoice_url' => 'https://example.com/invoice',
                ]
            ]
        ];

        // Envoyer la requête webhook
        $this->client->request(
            'POST',
            '/webhook/stripe',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => $this->generateHeader(["payload" => json_encode($payload)])],
            json_encode($payload)
        );

        // Vérifier la réponse
        $this->assertResponseStatusCodeSame(Response::HTTP_ACCEPTED);

        // Vérifier que la facture a été créée
        $invoice = $this->entityManager->getRepository(Invoice::class)->findOneBy(['stripeId' => 'in_123456']);
        $this->assertNotNull($invoice);

        //verif plus poussé de la sub ( month + 1 plus active plus id identique)
        $this->assertEquals('sub_123456', $invoice->getSubscription()->getStripeId());
        $this->assertTrue($invoice->getSubscription()->isActive());

        $this->assertEquals('INV-001', $invoice->getNumber());
        $this->assertEqualsWithDelta($oldCurrentPeriodEnd->modify('+1 month'), $invoice->getSubscription()->getCurrentPeriodEnd(), 5);
        $this->assertEquals(1000, $invoice->getAmountPaid());
        $this->assertEquals('https://example.com/invoice', $invoice->getHostedInvoiceUrl());
    }

    public function testInvalidEventType()
    {
        // Simuler un événement Stripe invalide
        $payload = [
            'type' => 'invalid.event',
            'data' => [
                'object' => []
            ]
        ];

        // Envoyer la requête webhook
        $this->client->request(
            'POST',
            '/webhook/stripe',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => 'test_signature'],
            json_encode($payload)
        );

        // Vérifier la réponse
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Nettoyer la base de données
        $this->entityManager->createQuery('DELETE FROM App\Entity\Invoice')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Subscription')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Plan')->execute();
        $this->entityManager->flush();
        $this->entityManager->close();
    }
}
