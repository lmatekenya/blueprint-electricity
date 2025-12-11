<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'electricity_transactions')]
#[ApiResource(
    operations: [
        new GetCollection(security: 'is_granted("PUBLIC_ACCESS")'),
        new Post(
            security: 'is_granted("ROLE_USER")',
            validationContext: ['groups' => ['create']]
        ),
        new Get(security: 'is_granted("ROLE_USER") and object.getUser() == user'),
    ],
    normalizationContext: ['groups' => ['transaction:read']],
    denormalizationContext: ['groups' => ['transaction:write', 'create']]
)]
class ElectricityTransaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['transaction:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 5, max: 50)]
    #[Groups(['transaction:read', 'transaction:write', 'create'])]
    private ?string $transID = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 10, max: 20)]
    #[Groups(['transaction:read', 'transaction:write', 'create'])]
    private ?string $meterNumber = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\Positive]
    #[Assert\GreaterThanOrEqual(value: 10)]
    #[Groups(['transaction:read', 'transaction:write', 'create'])]
    private ?string $amount = null;

    #[ORM\Column(length: 255)]
    #[Groups(['transaction:read', 'transaction:write'])]
    private ?string $status = 'pending';

    #[ORM\Column(length: 500, nullable: true)]
    #[Groups(['transaction:read'])]
    private ?string $token = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['transaction:read'])]
    private ?string $receiptNo = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['transaction:read'])]
    private ?int $units = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['transaction:read'])]
    private ?array $details = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['transaction:read'])]
    private ?string $provider = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['transaction:read'])]
    private ?User $user = null;

    #[ORM\Column]
    #[Groups(['transaction:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups(['transaction:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Getters and setters for all properties
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTransID(): ?string
    {
        return $this->transID;
    }

    public function setTransID(string $transID): static
    {
        $this->transID = $transID;
        return $this;
    }

    public function getMeterNumber(): ?string
    {
        return $this->meterNumber;
    }

    public function setMeterNumber(string $meterNumber): static
    {
        $this->meterNumber = $meterNumber;
        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): static
    {
        $this->token = $token;
        return $this;
    }

    public function getReceiptNo(): ?string
    {
        return $this->receiptNo;
    }

    public function setReceiptNo(?string $receiptNo): static
    {
        $this->receiptNo = $receiptNo;
        return $this;
    }

    public function getUnits(): ?int
    {
        return $this->units;
    }

    public function setUnits(?int $units): static
    {
        $this->units = $units;
        return $this;
    }

    public function getDetails(): ?array
    {
        return $this->details;
    }

    public function setDetails(?array $details): static
    {
        $this->details = $details;
        return $this;
    }

    public function getProvider(): ?string
    {
        return $this->provider;
    }

    public function setProvider(?string $provider): static
    {
        $this->provider = $provider;
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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}
