<?php

namespace TillKubelke\ModuleMarketplace\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use TillKubelke\ModuleMarketplace\Repository\PartnerBookmarkRepository;
use TillKubelke\PlatformFoundation\Tenant\Entity\Tenant;

/**
 * PartnerBookmark Entity - Manual partner marking.
 * 
 * Allows tenants to manually mark service providers as "their partners"
 * independent of any actual engagement/booking.
 */
#[ORM\Entity(repositoryClass: PartnerBookmarkRepository::class)]
#[ORM\Table(name: 'marketplace_partner_bookmarks')]
#[ORM\UniqueConstraint(name: 'tenant_provider_unique', columns: ['tenant_id', 'provider_id'])]
#[ORM\HasLifecycleCallbacks]
class PartnerBookmark
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Tenant::class)]
    #[ORM\JoinColumn(name: 'tenant_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Tenant $tenant;

    #[ORM\ManyToOne(targetEntity: ServiceProvider::class)]
    #[ORM\JoinColumn(name: 'provider_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ServiceProvider $provider;

    /**
     * Optional note about this partner relationship.
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $note = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = $this->createdAt ?? new \DateTimeImmutable();
    }

    // Getters and Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTenant(): Tenant
    {
        return $this->tenant;
    }

    public function setTenant(Tenant $tenant): static
    {
        $this->tenant = $tenant;
        return $this;
    }

    public function getProvider(): ServiceProvider
    {
        return $this->provider;
    }

    public function setProvider(ServiceProvider $provider): static
    {
        $this->provider = $provider;
        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenantId' => $this->tenant->getId(),
            'providerId' => $this->provider->getId(),
            'providerName' => $this->provider->getCompanyName(),
            'providerLogo' => $this->provider->getLogoUrl(),
            'note' => $this->note,
            'createdAt' => $this->createdAt?->format('c'),
        ];
    }
}




