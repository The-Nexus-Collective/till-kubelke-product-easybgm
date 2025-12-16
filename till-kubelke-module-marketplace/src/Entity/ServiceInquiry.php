<?php

namespace TillKubelke\ModuleMarketplace\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use TillKubelke\PlatformFoundation\Tenant\Entity\Tenant;

/**
 * ServiceInquiry Entity - Inquiry requests from tenants to providers.
 * 
 * Represents an inquiry sent by a company (tenant) to a service provider,
 * optionally linked to a specific offering and BGM project.
 */
#[ORM\Entity(repositoryClass: \TillKubelke\ModuleMarketplace\Repository\ServiceInquiryRepository::class)]
#[ORM\Table(name: 'marketplace_service_inquiries')]
#[ORM\HasLifecycleCallbacks]
class ServiceInquiry
{
    public const STATUS_NEW = 'new';
    public const STATUS_CONTACTED = 'contacted';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_DECLINED = 'declined';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Tenant::class)]
    #[ORM\JoinColumn(name: 'tenant_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Tenant $tenant = null;

    #[ORM\ManyToOne(targetEntity: ServiceProvider::class, inversedBy: 'inquiries')]
    #[ORM\JoinColumn(name: 'provider_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?ServiceProvider $provider = null;

    #[ORM\ManyToOne(targetEntity: ServiceOffering::class, inversedBy: 'inquiries')]
    #[ORM\JoinColumn(name: 'offering_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?ServiceOffering $offering = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $bgmProjectId = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    private string $contactName;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private string $contactEmail;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $contactPhone = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 20, max: 5000)]
    private string $message;

    #[ORM\Column(type: Types::STRING, length: 20, options: ['default' => 'new'])]
    private string $status = self::STATUS_NEW;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $providerNotes = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $respondedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTenant(): ?Tenant
    {
        return $this->tenant;
    }

    public function setTenant(?Tenant $tenant): static
    {
        $this->tenant = $tenant;
        return $this;
    }

    public function getProvider(): ?ServiceProvider
    {
        return $this->provider;
    }

    public function setProvider(?ServiceProvider $provider): static
    {
        $this->provider = $provider;
        return $this;
    }

    public function getOffering(): ?ServiceOffering
    {
        return $this->offering;
    }

    public function setOffering(?ServiceOffering $offering): static
    {
        $this->offering = $offering;
        return $this;
    }

    public function getBgmProjectId(): ?int
    {
        return $this->bgmProjectId;
    }

    public function setBgmProjectId(?int $bgmProjectId): static
    {
        $this->bgmProjectId = $bgmProjectId;
        return $this;
    }

    public function getContactName(): string
    {
        return $this->contactName;
    }

    public function setContactName(string $contactName): static
    {
        $this->contactName = $contactName;
        return $this;
    }

    public function getContactEmail(): string
    {
        return $this->contactEmail;
    }

    public function setContactEmail(string $contactEmail): static
    {
        $this->contactEmail = $contactEmail;
        return $this;
    }

    public function getContactPhone(): ?string
    {
        return $this->contactPhone;
    }

    public function setContactPhone(?string $contactPhone): static
    {
        $this->contactPhone = $contactPhone;
        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        if ($status !== self::STATUS_NEW && $this->respondedAt === null) {
            $this->respondedAt = new \DateTimeImmutable();
        }
        return $this;
    }

    public function isNew(): bool
    {
        return $this->status === self::STATUS_NEW;
    }

    public function isContacted(): bool
    {
        return $this->status === self::STATUS_CONTACTED;
    }

    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isDeclined(): bool
    {
        return $this->status === self::STATUS_DECLINED;
    }

    public function markAsContacted(): static
    {
        return $this->setStatus(self::STATUS_CONTACTED);
    }

    public function markAsInProgress(): static
    {
        return $this->setStatus(self::STATUS_IN_PROGRESS);
    }

    public function markAsCompleted(): static
    {
        return $this->setStatus(self::STATUS_COMPLETED);
    }

    public function markAsDeclined(): static
    {
        return $this->setStatus(self::STATUS_DECLINED);
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): static
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function getProviderNotes(): ?string
    {
        return $this->providerNotes;
    }

    public function setProviderNotes(?string $providerNotes): static
    {
        $this->providerNotes = $providerNotes;
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

    public function getRespondedAt(): ?\DateTimeImmutable
    {
        return $this->respondedAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenantId' => $this->tenant?->getId(),
            'tenantName' => $this->tenant?->getName(),
            'providerId' => $this->provider?->getId(),
            'providerName' => $this->provider?->getCompanyName(),
            'offeringId' => $this->offering?->getId(),
            'offeringTitle' => $this->offering?->getTitle(),
            'bgmProjectId' => $this->bgmProjectId,
            'contactName' => $this->contactName,
            'contactEmail' => $this->contactEmail,
            'contactPhone' => $this->contactPhone,
            'message' => $this->message,
            'status' => $this->status,
            'metadata' => $this->metadata,
            'providerNotes' => $this->providerNotes,
            'createdAt' => $this->createdAt?->format('c'),
            'updatedAt' => $this->updatedAt?->format('c'),
            'respondedAt' => $this->respondedAt?->format('c'),
        ];
    }
}

