<?php

namespace TillKubelke\ModuleMarketplace\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use TillKubelke\ModuleMarketplace\Repository\PartnerEngagementRepository;
use TillKubelke\PlatformFoundation\Tenant\Entity\Tenant;

/**
 * PartnerEngagement Entity - Tracks active collaborations between tenants and service providers.
 * 
 * Represents the full lifecycle of a partner engagement:
 * 1. draft     - Initial inquiry accepted, terms being discussed
 * 2. active    - Engagement confirmed, waiting for data sharing
 * 3. data_shared - Customer has granted required data access
 * 4. processing - Partner is working on deliverables
 * 5. delivered - Partner has uploaded results
 * 6. completed - Customer has reviewed and accepted results
 * 7. cancelled - Engagement was cancelled
 */
#[ORM\Entity(repositoryClass: PartnerEngagementRepository::class)]
#[ORM\Table(name: 'marketplace_partner_engagements')]
#[ORM\HasLifecycleCallbacks]
class PartnerEngagement
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_DATA_SHARED = 'data_shared';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const VALID_STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_ACTIVE,
        self::STATUS_DATA_SHARED,
        self::STATUS_PROCESSING,
        self::STATUS_DELIVERED,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Tenant::class)]
    #[ORM\JoinColumn(name: 'tenant_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?Tenant $tenant = null;

    #[ORM\ManyToOne(targetEntity: ServiceProvider::class)]
    #[ORM\JoinColumn(name: 'provider_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?ServiceProvider $provider = null;

    #[ORM\ManyToOne(targetEntity: ServiceOffering::class)]
    #[ORM\JoinColumn(name: 'offering_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?ServiceOffering $offering = null;

    /**
     * Original inquiry that led to this engagement (if any).
     */
    #[ORM\OneToOne(targetEntity: ServiceInquiry::class)]
    #[ORM\JoinColumn(name: 'inquiry_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?ServiceInquiry $inquiry = null;

    #[ORM\Column(type: Types::STRING, length: 20, options: ['default' => 'draft'])]
    private string $status = self::STATUS_DRAFT;

    // ========== Data Sharing ==========

    /**
     * Data scopes that the customer has granted access to.
     * Structure: {"scope_key": {"granted_at": "...", "status": "granted|revoked"}}
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $grantedDataScopes = null;

    /**
     * Actual data that has been shared with the partner.
     * Structure: {"scope_key": {"shared_at": "...", "data_ref": "..."}}
     * Note: Actual data is stored separately, this just tracks what was shared.
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $sharedData = null;

    // ========== Deliverables ==========

    /**
     * Results/outputs delivered by the partner.
     * Structure: {"output_type": {"delivered_at": "...", "file_url": "...", "data": {...}}}
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $deliveredOutputs = null;

    /**
     * Integration status - which results have been "plugged in".
     * Structure: {"output_type": {"integrated_at": "...", "integration_point": "..."}}
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $integrationStatus = null;

    // ========== Partner Contact ==========

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $partnerContactName = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Assert\Email]
    private ?string $partnerContactEmail = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $partnerContactPhone = null;

    // ========== Notes & Communication ==========

    /**
     * Internal notes from the customer (not visible to partner).
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $customerNotes = null;

    /**
     * Notes from the partner (visible to customer).
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $partnerNotes = null;

    // ========== Pricing & Terms ==========

    /**
     * Agreed pricing for this engagement.
     * Structure: {"amount": 1500, "currency": "EUR", "type": "fixed|hourly|per_participant"}
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $agreedPricing = null;

    /**
     * Scheduled date for the service (if applicable).
     */
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $scheduledDate = null;

    // ========== Timestamps ==========

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $activatedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $cancelledAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // ========== ID ==========

    public function getId(): ?int
    {
        return $this->id;
    }

    // ========== Relationships ==========

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

    public function getInquiry(): ?ServiceInquiry
    {
        return $this->inquiry;
    }

    public function setInquiry(?ServiceInquiry $inquiry): static
    {
        $this->inquiry = $inquiry;
        return $this;
    }

    // ========== Status ==========

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        if (!in_array($status, self::VALID_STATUSES, true)) {
            throw new \InvalidArgumentException("Invalid status: {$status}");
        }
        $this->status = $status;
        return $this;
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isDataShared(): bool
    {
        return $this->status === self::STATUS_DATA_SHARED;
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function isDelivered(): bool
    {
        return $this->status === self::STATUS_DELIVERED;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Check if this engagement is still active (not completed or cancelled).
     */
    public function isOngoing(): bool
    {
        return !$this->isCompleted() && !$this->isCancelled();
    }

    // ========== Status Transitions ==========

    public function activate(): static
    {
        $this->status = self::STATUS_ACTIVE;
        $this->activatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function markDataShared(): static
    {
        $this->status = self::STATUS_DATA_SHARED;
        return $this;
    }

    public function markProcessing(): static
    {
        $this->status = self::STATUS_PROCESSING;
        return $this;
    }

    public function markDelivered(): static
    {
        $this->status = self::STATUS_DELIVERED;
        return $this;
    }

    public function complete(): static
    {
        $this->status = self::STATUS_COMPLETED;
        $this->completedAt = new \DateTimeImmutable();
        return $this;
    }

    public function cancel(): static
    {
        $this->status = self::STATUS_CANCELLED;
        $this->cancelledAt = new \DateTimeImmutable();
        return $this;
    }

    // ========== Data Sharing ==========

    public function getGrantedDataScopes(): ?array
    {
        return $this->grantedDataScopes;
    }

    public function setGrantedDataScopes(?array $grantedDataScopes): static
    {
        $this->grantedDataScopes = $grantedDataScopes;
        return $this;
    }

    /**
     * Grant access to a specific data scope.
     */
    public function grantDataScope(string $scope): static
    {
        $this->grantedDataScopes ??= [];
        $this->grantedDataScopes[$scope] = [
            'granted_at' => (new \DateTimeImmutable())->format('c'),
            'status' => 'granted',
        ];
        return $this;
    }

    /**
     * Revoke access to a specific data scope.
     */
    public function revokeDataScope(string $scope): static
    {
        if (isset($this->grantedDataScopes[$scope])) {
            $this->grantedDataScopes[$scope]['status'] = 'revoked';
            $this->grantedDataScopes[$scope]['revoked_at'] = (new \DateTimeImmutable())->format('c');
        }
        return $this;
    }

    /**
     * Check if a data scope is currently granted.
     */
    public function hasGrantedScope(string $scope): bool
    {
        return isset($this->grantedDataScopes[$scope]) 
            && ($this->grantedDataScopes[$scope]['status'] ?? '') === 'granted';
    }

    /**
     * Get list of currently granted scope keys.
     */
    public function getGrantedScopeKeys(): array
    {
        if ($this->grantedDataScopes === null) {
            return [];
        }
        return array_keys(array_filter(
            $this->grantedDataScopes,
            fn(array $scope) => ($scope['status'] ?? '') === 'granted'
        ));
    }

    public function getSharedData(): ?array
    {
        return $this->sharedData;
    }

    public function setSharedData(?array $sharedData): static
    {
        $this->sharedData = $sharedData;
        return $this;
    }

    // ========== Deliverables ==========

    public function getDeliveredOutputs(): ?array
    {
        return $this->deliveredOutputs;
    }

    public function setDeliveredOutputs(?array $deliveredOutputs): static
    {
        $this->deliveredOutputs = $deliveredOutputs;
        return $this;
    }

    /**
     * Record a delivered output from the partner.
     */
    public function addDeliveredOutput(string $outputType, array $data): static
    {
        $this->deliveredOutputs ??= [];
        $this->deliveredOutputs[$outputType] = [
            'delivered_at' => (new \DateTimeImmutable())->format('c'),
            'data' => $data,
        ];
        return $this;
    }

    /**
     * Check if a specific output has been delivered.
     */
    public function hasDeliveredOutput(string $outputType): bool
    {
        return isset($this->deliveredOutputs[$outputType]);
    }

    public function getIntegrationStatus(): ?array
    {
        return $this->integrationStatus;
    }

    public function setIntegrationStatus(?array $integrationStatus): static
    {
        $this->integrationStatus = $integrationStatus;
        return $this;
    }

    /**
     * Mark an output as integrated.
     */
    public function markOutputIntegrated(string $outputType, string $integrationPoint): static
    {
        $this->integrationStatus ??= [];
        $this->integrationStatus[$outputType] = [
            'integrated_at' => (new \DateTimeImmutable())->format('c'),
            'integration_point' => $integrationPoint,
        ];
        return $this;
    }

    // ========== Partner Contact ==========

    public function getPartnerContactName(): ?string
    {
        return $this->partnerContactName;
    }

    public function setPartnerContactName(?string $partnerContactName): static
    {
        $this->partnerContactName = $partnerContactName;
        return $this;
    }

    public function getPartnerContactEmail(): ?string
    {
        return $this->partnerContactEmail;
    }

    public function setPartnerContactEmail(?string $partnerContactEmail): static
    {
        $this->partnerContactEmail = $partnerContactEmail;
        return $this;
    }

    public function getPartnerContactPhone(): ?string
    {
        return $this->partnerContactPhone;
    }

    public function setPartnerContactPhone(?string $partnerContactPhone): static
    {
        $this->partnerContactPhone = $partnerContactPhone;
        return $this;
    }

    // ========== Notes ==========

    public function getCustomerNotes(): ?string
    {
        return $this->customerNotes;
    }

    public function setCustomerNotes(?string $customerNotes): static
    {
        $this->customerNotes = $customerNotes;
        return $this;
    }

    public function getPartnerNotes(): ?string
    {
        return $this->partnerNotes;
    }

    public function setPartnerNotes(?string $partnerNotes): static
    {
        $this->partnerNotes = $partnerNotes;
        return $this;
    }

    // ========== Pricing & Terms ==========

    public function getAgreedPricing(): ?array
    {
        return $this->agreedPricing;
    }

    public function setAgreedPricing(?array $agreedPricing): static
    {
        $this->agreedPricing = $agreedPricing;
        return $this;
    }

    public function getScheduledDate(): ?\DateTimeInterface
    {
        return $this->scheduledDate;
    }

    public function setScheduledDate(?\DateTimeInterface $scheduledDate): static
    {
        $this->scheduledDate = $scheduledDate;
        return $this;
    }

    // ========== Timestamps ==========

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getActivatedAt(): ?\DateTimeImmutable
    {
        return $this->activatedAt;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function getCancelledAt(): ?\DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    // ========== Serialization ==========

    public function toArray(bool $includeNotes = false): array
    {
        $data = [
            'id' => $this->id,
            'tenantId' => $this->tenant?->getId(),
            'providerId' => $this->provider?->getId(),
            'providerName' => $this->provider?->getCompanyName(),
            'offeringId' => $this->offering?->getId(),
            'offeringTitle' => $this->offering?->getTitle(),
            'inquiryId' => $this->inquiry?->getId(),
            'status' => $this->status,
            'grantedDataScopes' => $this->getGrantedScopeKeys(),
            'deliveredOutputs' => $this->deliveredOutputs ? array_keys($this->deliveredOutputs) : [],
            'integrationStatus' => $this->integrationStatus,
            'partnerContact' => [
                'name' => $this->partnerContactName,
                'email' => $this->partnerContactEmail,
                'phone' => $this->partnerContactPhone,
            ],
            'agreedPricing' => $this->agreedPricing,
            'scheduledDate' => $this->scheduledDate?->format('Y-m-d'),
            'createdAt' => $this->createdAt?->format('c'),
            'updatedAt' => $this->updatedAt?->format('c'),
            'activatedAt' => $this->activatedAt?->format('c'),
            'completedAt' => $this->completedAt?->format('c'),
            'cancelledAt' => $this->cancelledAt?->format('c'),
        ];

        if ($includeNotes) {
            $data['customerNotes'] = $this->customerNotes;
            $data['partnerNotes'] = $this->partnerNotes;
        }

        return $data;
    }
}
