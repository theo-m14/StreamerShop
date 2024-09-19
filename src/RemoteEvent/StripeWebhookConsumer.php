<?php

namespace App\RemoteEvent;

use DateTime;
use App\Entity\Invoice;
use Stripe\Subscription;
use App\Repository\PlanRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\SubscriptionRepository;
use Symfony\Component\RemoteEvent\RemoteEvent;
use App\Entity\Subscription as AppSubscription;
use Symfony\Component\RemoteEvent\Consumer\ConsumerInterface;
use Symfony\Component\RemoteEvent\Attribute\AsRemoteEventConsumer;

#[AsRemoteEventConsumer('stripe')]
final class StripeWebhookConsumer implements ConsumerInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly SubscriptionRepository $subscriptionRepository,
        private readonly PlanRepository $planRepository,
    ) {
    }

    public function consume(RemoteEvent $event): void
    {
        //Event handling
        if ($event->getName() === 'checkout.session.completed') {
            $this->handleCheckoutSessionCompleted($event);
        }

        if ($event->getName() === 'invoice.paid') {
            $this->handleInvoicePaid($event);
        }
    }

    private function handleCheckoutSessionCompleted(RemoteEvent $event): void
    {
        //Subscription creation from RemoteEvent
        $user = $event->getPayload()['user'];
        $subscription = new AppSubscription();
        
        $plan = $event->getPayload()['plan'];
        $subscription->setPlan($plan);
        $subscription->setStripeId($event->getId());
        $subscription->setCurrentPeriodStart(new \Datetime(date('c', $event->getPayload()['current_period_start'])));
        $subscription->setCurrentPeriodEnd(new \Datetime(date('c', $event->getPayload()['current_period_end'])));
        $subscription->setUser($user);
        $subscription->setActive(true);
        $user->setStripeId($event->getPayload()['user_stripe_id']);

        // Disable old subscription if needed , repository method have to be create, Stripe::update method to implement in Service\PaymentApiClient
        // $activeSub = $subscriptionRepository->findActiveSub($user->getId());
        // if ($activeSub) {
        //     \Stripe\Subscription::update(
        //         $activeSub->getStripeId(), [
        //             'cancel_at_period_end' => false,
        //         ]
        //     );
        //     $activeSub->setIsActive(false);
        //     $this->entityManager->persist($activeSub);
        // }

        $this->entityManager->persist($subscription);
        $this->entityManager->flush();

    }

    private function handleInvoicePaid(RemoteEvent $event): void
    {
        //Invoice creation from RemoteEvent
        $subscription = $this->subscriptionRepository->findOneBy(["stripeId" => $event->getId()]);

        $invoice = new Invoice();
        $invoice->setStripeId($event->getPayload()['id']);
        $invoice->setSubscription($subscription);
        $invoice->setNumber($event->getPayload()['number']);
        $invoice->setAmountPaid($event->getPayload()['amount_paid']);
        $invoice->setCreatedAt(new \DateTimeImmutable());
        $invoice->setHostedInvoiceUrl($event->getPayload()['hosted_invoice_url']);
        $invoice->setUser($subscription->getUser());

        //Subscription update from RemoteEvent
        $subscription->setCurrentPeriodEnd((new DateTime())->modify('+1 month'));
        if(!$subscription->isActive()){
            $subscription->setActive(true);
        }
        $this->entityManager->persist($subscription);
        $this->entityManager->persist($invoice);
        //Invoice and Subscription flush
        
        $this->entityManager->flush();
    }
}
