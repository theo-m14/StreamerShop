<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\OrderItemRepository;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: OrderItemRepository::class)]
class OrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['read:order'])]
    private ?int $id = null;

    #[ORM\Column]
    #[Groups(['read:order'])]
    private ?int $quantity = null;

    #[ORM\ManyToOne(inversedBy: 'orderItems')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['read:order'])]
    private ?Product $product = null;

    #[ORM\ManyToOne(inversedBy: 'orderItem')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Order $associatedOrder = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getAssociatedOrder(): ?Order
    {
        return $this->associatedOrder;
    }

    public function setAssociatedOrder(?Order $associatedOrder): static
    {
        $this->associatedOrder = $associatedOrder;

        return $this;
    }
}
