<?php
// src/Entity/ElectricityToken.php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: "App\Repository\ElectricityTokenRepository")]
#[ORM\Table(name: 'electricity_tokens')]
class ElectricityToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    // store hashed token only (unique constraint on hash)
    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $tokenHash;

    // store an optional short token preview for admin UIs (e.g. last 4)
    #[ORM\Column(type: 'string', length: 8, nullable: true)]
    private ?string $preview = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $meterNumber;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $amount;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column(type: 'boolean')]
    private bool $used = false;

    #[ORM\ManyToOne(targetEntity: ElectricityTransaction::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: true)]
    private ?ElectricityTransaction $transaction = null;

    public function __construct(string $tokenHash, string $meterNumber, string $amount, ?\DateTimeImmutable $expiresAt = null, ?string $preview = null)
    {
        $this->tokenHash = $tokenHash;
        $this->meterNumber = $meterNumber;
        $this->amount = $amount;
        $this->createdAt = new \DateTimeImmutable();
        $this->expiresAt = $expiresAt;
        $this->used = false;
        $this->preview = $preview;
    }

    public function getId(): ?int { return $this->id; }
    public function getTokenHash(): string { return $this->tokenHash; }
    public function getPreview(): ?string { return $this->preview; }
    public function getMeterNumber(): string { return $this->meterNumber; }
    public function getAmount(): string { return $this->amount; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getExpiresAt(): ?\DateTimeImmutable { return $this->expiresAt; }
    public function isUsed(): bool { return $this->used; }
    public function setUsed(bool $used): static { $this->used = $used; return $this; }
    public function getTransaction(): ?ElectricityTransaction { return $this->transaction; }
    public function setTransaction(?ElectricityTransaction $t): static { $this->transaction = $t; return $this; }
}
