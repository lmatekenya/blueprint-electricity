<?php
// src/Entity/ElectricityTariff.php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: "App\Repository\ElectricityTariffRepository")]
#[ORM\Table(name: 'electricity_tariffs')]
class ElectricityTariff
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ElectricityProvider::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?ElectricityProvider $provider = null;

    // decimal rate (e.g. 1.3200)
    #[ORM\Column(type: 'decimal', precision: 10, scale: 4)]
    private string $rate;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $band = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(string $rate, ?ElectricityProvider $provider = null, ?string $band = null)
    {
        $this->rate = $rate;
        $this->provider = $provider;
        $this->band = $band;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getProvider(): ?ElectricityProvider { return $this->provider; }
    public function setProvider(?ElectricityProvider $p): static { $this->provider = $p; return $this; }
    public function getRate(): string { return $this->rate; }
    public function setRate(string $rate): static { $this->rate = $rate; return $this; }
    public function getBand(): ?string { return $this->band; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
