<?php

namespace App\RemoteEvent;

use App\Entity\Shipment;
use App\Entity\ShipmentStatut;
use App\Repository\ShipmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ShipmentStatutRepository;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Symfony\Component\RemoteEvent\Consumer\ConsumerInterface;
use Symfony\Component\RemoteEvent\Attribute\AsRemoteEventConsumer;

#[AsRemoteEventConsumer('boxtal')]
final class BoxtalWebhookConsumer implements ConsumerInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    )
    {
    }

    public function consume(RemoteEvent $event): void
    {
        if ($event->getName() === 'TRACKING_CHANGED') {
            $this->handleTrackingChanged($event);
        }
    }

    private function handleTrackingChanged(RemoteEvent $event): void
    {
        $payload = $event->getPayload();

        $payload = $payload['payload'];

        $tracking = $payload['trackings'][0];

        $shippingOrderId = $event->getId();

        $shipment = $this->entityManager->getRepository(Shipment::class)->find($shippingOrderId);

        switch ($tracking['status']) {
            case 'DELIVERED':
                $shipment->setStatut($this->getShipmentStatut('delivered'));
                break;
            case 'SHIPPED':
                $this->setPackageTrackingUrl($shipment, $tracking['packageTrackingUrl']);
                $shipment->setStatut($this->getShipmentStatut('shipped'));
                break;
        }
    }

    private function getShipmentStatut(string $status): ShipmentStatut
    {
        $shipmentStatut = $this->entityManager->getRepository(ShipmentStatut::class)->findOneBy(['statut' => $status]);

        if (!$shipmentStatut) {
            $this->createShipmentStatut($status);
        }

        return $shipmentStatut;
    }

    private function createShipmentStatut(string $status): void
    {
        $shipmentStatut = new ShipmentStatut();
        $shipmentStatut->setStatut($status);
        $this->entityManager->persist($shipmentStatut);
        $this->entityManager->flush();
    }

    private function setPackageTrackingUrl(Shipment $shipment, string $trackingUrl): void
    {
        $shipment->setPackageTrackingUrl($trackingUrl);
        $this->entityManager->persist($shipment);
        $this->entityManager->flush();
    }
}
