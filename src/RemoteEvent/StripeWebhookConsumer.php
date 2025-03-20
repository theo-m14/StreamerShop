<?php

namespace App\RemoteEvent;


use App\Entity\OrderStatut;
use App\Repository\UserRepository;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Symfony\Component\RemoteEvent\Consumer\ConsumerInterface;
use Symfony\Component\RemoteEvent\Attribute\AsRemoteEventConsumer;


#[AsRemoteEventConsumer('stripe')]
final class StripeWebhookConsumer implements ConsumerInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly OrderRepository $orderRepository,
    ) {
    }

    public function consume(RemoteEvent $event): void
    {
        //Event handling
        if ($event->getName() === 'checkout.session.completed') {
            $this->handleCheckoutSessionCompleted($event);
        }

        if ($event->getName() === 'checkout.session.async_payment_succeeded') {
            $this->handleCheckoutSessionAsyncPaymentSucceeded($event);
        }

        if ($event->getName() === 'checkout.session.async_payment_failed') {
            $this->handleCheckoutSessionAsyncPaymentFailed($event);
        }
    }

    private function handleCheckoutSessionCompleted(RemoteEvent $event): void
    {
        //Subscription creation from RemoteEvent
        $order = $this->orderRepository->findOneBy(["checkoutSessionId" => $event->getId()]);

        switch($event->getPayload()['statut']){
            case 'paid':
                $order->setStatut($this->entityManager->getRepository(OrderStatut::class)->findOneBy(['statut' => 'paid']));
                break;
            case 'unpaid':
                $order->setStatut($this->entityManager->getRepository(OrderStatut::class)->findOneBy(['statut' => 'waitingPayment']));
                break;
        }

        $this->entityManager->persist($order);
        $this->entityManager->flush();
    }

    private function handleCheckoutSessionAsyncPaymentSucceeded(RemoteEvent $event): void
    {
        $order = $this->orderRepository->findOneBy(["checkoutSessionId" => $event->getId()]);

        $order->setStatut($this->entityManager->getRepository(OrderStatut::class)->findOneBy(['statut' => 'paid']));
        $this->entityManager->persist($order);
        $this->entityManager->flush();
    }

    private function handleCheckoutSessionAsyncPaymentFailed(RemoteEvent $event): void
    {
        $order = $this->orderRepository->findOneBy(["checkoutSessionId" => $event->getId()]);

        $order->setStatut($this->entityManager->getRepository(OrderStatut::class)->findOneBy(['statut' => 'failed']));
        $this->entityManager->persist($order);
        $this->entityManager->flush();
    }

    // private function handleInvoicePaid(RemoteEvent $event): void
    // {
    //     //Invoice creation from RemoteEvent
    //     $subscription = $this->subscriptionRepository->findOneBy(["stripeId" => $event->getId()]);

    //     $invoice = new Invoice();
    //     $invoice->setStripeId($event->getPayload()['id']);
    //     $invoice->setSubscription($subscription);
    //     $invoice->setNumber($event->getPayload()['number']);
    //     $invoice->setAmountPaid($event->getPayload()['amount_paid']);
    //     $invoice->setCreatedAt(new \DateTimeImmutable());
    //     $invoice->setHostedInvoiceUrl($event->getPayload()['hosted_invoice_url']);
    //     $invoice->setUser($subscription->getUser());

    //     //Subscription update from RemoteEvent
    //     $subscription->setCurrentPeriodEnd((new DateTime())->modify('+1 month'));
    //     if(!$subscription->isActive()){
    //         $subscription->setActive(true);
    //     }
    //     $this->entityManager->persist($subscription);
    //     $this->entityManager->persist($invoice);
    //     //Invoice and Subscription flush
        
    //     $this->entityManager->flush();
    // }
}
