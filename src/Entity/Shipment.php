<?php

namespace App\Entity;

use App\Repository\ShipmentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ShipmentRepository::class)]
class Shipment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $shipmentId = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $shippedAt = null;

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

    public function getShippedAt(): ?\DateTimeImmutable
    {
        return $this->shippedAt;
    }

    public function setShippedAt(\DateTimeImmutable $shippedAt): static
    {
        $this->shippedAt = $shippedAt;

        return $this;
    }
}
