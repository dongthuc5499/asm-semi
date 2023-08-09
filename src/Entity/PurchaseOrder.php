<?php

namespace App\Entity;

use App\Repository\PurchaseOrderRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;

#[ORM\Entity(repositoryClass: PurchaseOrderRepository::class)]
#[ORM\HasLifecycleCallbacks]
class PurchaseOrder
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'PurchaseOrder')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    private ?string $purchaseImage = null;

    #[ORM\Column]
    private ?int $price = null;

    #[ORM\Column]
    private ?int $quantity = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $id_order = null;

    #[ORM\Column]
    private ?int $product_id = null;

    #[ORM\Column]
    private ?int $status = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\PrePersist]
    public function prePersist(): void
    {
        $this->date = new \DateTime();
    }

    public static int $currentIdOrder = 0;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getPurchaseImage(): ?string
    {
        return $this->purchaseImage;
    }

    public function setPurchaseImage(string $purchaseImage): static
    {
        $this->purchaseImage = $purchaseImage;

        return $this;
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(int $price): static
    {
        $this->price = $price;

        return $this;
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getIdOrder(): ?int
    {
        return $this->id_order;
    }

    public function setIdOrder(int $id_order): self
    {
        $this->id_order = $id_order;

        return $this;
    }

    public function getProductId(): ?int
    {
        return $this->product_id;
    }

    public function setProductId(int $product_id): static
    {
        $this->product_id = $product_id;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }
}
