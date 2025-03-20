<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\AdressRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: AdressRepository::class)]
class Adress
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['read:order', 'read:user'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    #[ORM\Column(length: 255)]
    #[Groups(['read:order', 'read:user'])]
    private ?string $countryCode = null;

    #[ORM\Column(length: 255)]
    #[Groups(['read:order', 'read:user'])]
    private ?string $city = null;

    #[ORM\Column(length: 255)]
    #[Groups(['read:order', 'read:user'])]
    private ?string $postalCode = null;

    #[ORM\Column(length: 255)]
    #[Groups(['read:order', 'read:user'])]
    private ?string $adressLine = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $additionalInformation = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['read:order', 'read:user'])]
    private ?Contact $contact = null;

    /**
     * @var Collection<int, Order>
     */
    #[ORM\OneToMany(targetEntity: Order::class, mappedBy: 'adress')]
    private Collection $orders;

    #[ORM\Column(length: 255)]
    #[Groups(['read:user'])]
    private ?string $parcelPointCode = null;

    #[ORM\ManyToOne(inversedBy: 'adress')]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    #[Groups(['read:user', 'read:order'])]
    private ?string $parcelPointName = null;

    public function __construct()
    {
        $this->orders = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    public function setCountryCode(string $countryCode): static
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(string $postalCode): static
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    public function getadressLine(): ?string
    {
        return $this->adressLine;
    }

    public function setadressLine(string $adressLine): static
    {
        $this->adressLine = $adressLine;

        return $this;
    }

    public function getAdditionalInformation(): ?string
    {
        return $this->additionalInformation;
    }

    public function setAdditionalInformation(?string $additionalInformation): static
    {
        $this->additionalInformation = $additionalInformation;

        return $this;
    }

    public function getContact(): ?Contact
    {
        return $this->contact;
    }

    public function setContact(Contact $contact): static
    {
        $this->contact = $contact;

        return $this;
    }

    /**
     * @return Collection<int, Order>
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function addOrder(Order $order): static
    {
        if (!$this->orders->contains($order)) {
            $this->orders->add($order);
            $order->setAdress($this);
        }

        return $this;
    }

    public function removeOrder(Order $order): static
    {
        if ($this->orders->removeElement($order)) {
            // set the owning side to null (unless already changed)
            if ($order->getAdress() === $this) {
                $order->setAdress(null);
            }
        }

        return $this;
    }

    public function getParcelPointCode(): ?string
    {
        return $this->parcelPointCode;
    }

    public function setParcelPointCode(string $parcelPointCode): static
    {
        $this->parcelPointCode = $parcelPointCode;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getParcelPointName(): ?string
    {
        return $this->parcelPointName;
    }

    public function setParcelPointName(string $parcelPointName): static
    {
        $this->parcelPointName = $parcelPointName;

        return $this;
    }
}
