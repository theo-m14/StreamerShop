<?php

namespace App\Entity;

use App\Repository\ShipmentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
#[ORM\Entity(repositoryClass: ShipmentRepository::class)]
class Shipment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['read:shipment'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['read:shipment'])]
    private ?string $shipmentId = null;

    #[ORM\Column]
    #[Groups(['read:shipment'])]
    private ?int $deliveryPrice = null;

    #[ORM\Column]
    #[Groups(['read:shipment'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'shipments')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['read:shipment'])]
    private ?ShipmentStatut $statut = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['read:shipment'])]
    private ?string $packageTrackingUrl = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getShipmentId(): ?string
    {
        return $this->shipmentId;
    }

    public function setShipmentId(string $shipmentId): static
    {
        $this->shipmentId = $shipmentId;

        return $this;
    }

    public function getDeliveryPrice(): ?int
    {
        return $this->deliveryPrice;
    }

    public function setDeliveryPrice(int $deliveryPrice): static
    {
        $this->deliveryPrice = $deliveryPrice;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getStatut(): ?ShipmentStatut
    {
        return $this->statut;
    }

    public function setStatut(?ShipmentStatut $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getPackageTrackingUrl(): ?string
    {
        return $this->packageTrackingUrl;
    }

    public function setPackageTrackingUrl(?string $packageTrackingUrl): static
    {
        $this->packageTrackingUrl = $packageTrackingUrl;

        return $this;
    }
}
