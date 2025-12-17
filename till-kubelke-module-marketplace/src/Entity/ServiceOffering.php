<?php

namespace TillKubelke\ModuleMarketplace\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * ServiceOffering Entity - Specific services offered by BGM providers.
 * 
 * Represents individual service offerings that a provider can offer
 * to companies, such as workshops, fitness programs, coaching sessions, etc.
 */
#[ORM\Entity(repositoryClass: \TillKubelke\ModuleMarketplace\Repository\ServiceOfferingRepository::class)]
#[ORM\Table(name: 'marketplace_service_offerings')]
#[ORM\HasLifecycleCallbacks]
class ServiceOffering
{
    public const DELIVERY_ONSITE = 'onsite';
    public const DELIVERY_REMOTE = 'remote';
    public const DELIVERY_HYBRID = 'hybrid';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ServiceProvider::class, inversedBy: 'offerings')]
    #[ORM\JoinColumn(name: 'provider_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?ServiceProvider $provider = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $title;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    private string $description;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $pricingInfo = null;

    #[ORM\Column(type: Types::JSON)]
    private array $deliveryModes = [self::DELIVERY_ONSITE];

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isCertified = false;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $certificationName = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $duration = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $minParticipants = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $maxParticipants = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $sortOrder = 0;

    // ========== Partner Integration Fields ==========

    /**
     * Data scopes required from the customer to deliver this service.
     * e.g. ["employee_list", "goals", "survey_results"]
     * @see DataScopeRegistry for available scopes
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $requiredDataScopes = null;

    /**
     * Types of data/results this service delivers back.
     * e.g. ["copsoq_analysis", "participation_stats", "health_report"]
     * @see OutputTypeRegistry for available types
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $outputDataTypes = null;

    /**
     * Where the results "plug in" to the BGM process.
     * e.g. ["phase_2.analysis", "kpi.custom", "legal.gefaehrdungsbeurteilung"]
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $integrationPoints = null;

    /**
     * Which BGM phases this offering is relevant for (1-6).
     * e.g. [2, 3, 4] for Analysis, Planning, Implementation phases
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $relevantPhases = null;

    /**
     * Is this an orchestrator service that coordinates other providers?
     * e.g. Health insurance offering full-service health day planning
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isOrchestratorService = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, ServiceInquiry>
     */
    #[ORM\OneToMany(mappedBy: 'offering', targetEntity: ServiceInquiry::class)]
    private Collection $inquiries;

    public function __construct()
    {
        $this->inquiries = new ArrayCollection();
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

    public function getProvider(): ?ServiceProvider
    {
        return $this->provider;
    }

    public function setProvider(?ServiceProvider $provider): static
    {
        $this->provider = $provider;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getPricingInfo(): ?array
    {
        return $this->pricingInfo;
    }

    public function setPricingInfo(?array $pricingInfo): static
    {
        $this->pricingInfo = $pricingInfo;
        return $this;
    }

    public function getDeliveryModes(): array
    {
        return $this->deliveryModes;
    }

    public function setDeliveryModes(array $deliveryModes): static
    {
        $this->deliveryModes = $deliveryModes;
        return $this;
    }

    public function supportsOnsite(): bool
    {
        return in_array(self::DELIVERY_ONSITE, $this->deliveryModes, true);
    }

    public function supportsRemote(): bool
    {
        return in_array(self::DELIVERY_REMOTE, $this->deliveryModes, true);
    }

    public function supportsHybrid(): bool
    {
        return in_array(self::DELIVERY_HYBRID, $this->deliveryModes, true);
    }

    public function isCertified(): bool
    {
        return $this->isCertified;
    }

    public function setIsCertified(bool $isCertified): static
    {
        $this->isCertified = $isCertified;
        return $this;
    }

    public function getCertificationName(): ?string
    {
        return $this->certificationName;
    }

    public function setCertificationName(?string $certificationName): static
    {
        $this->certificationName = $certificationName;
        return $this;
    }

    public function getDuration(): ?string
    {
        return $this->duration;
    }

    public function setDuration(?string $duration): static
    {
        $this->duration = $duration;
        return $this;
    }

    public function getMinParticipants(): ?int
    {
        return $this->minParticipants;
    }

    public function setMinParticipants(?int $minParticipants): static
    {
        $this->minParticipants = $minParticipants;
        return $this;
    }

    public function getMaxParticipants(): ?int
    {
        return $this->maxParticipants;
    }

    public function setMaxParticipants(?int $maxParticipants): static
    {
        $this->maxParticipants = $maxParticipants;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;
        return $this;
    }

    // ========== Partner Integration Getters/Setters ==========

    public function getRequiredDataScopes(): ?array
    {
        return $this->requiredDataScopes;
    }

    public function setRequiredDataScopes(?array $requiredDataScopes): static
    {
        $this->requiredDataScopes = $requiredDataScopes;
        return $this;
    }

    /**
     * Check if this offering requires a specific data scope.
     */
    public function requiresDataScope(string $scope): bool
    {
        return $this->requiredDataScopes !== null 
            && in_array($scope, $this->requiredDataScopes, true);
    }

    public function getOutputDataTypes(): ?array
    {
        return $this->outputDataTypes;
    }

    public function setOutputDataTypes(?array $outputDataTypes): static
    {
        $this->outputDataTypes = $outputDataTypes;
        return $this;
    }

    /**
     * Check if this offering delivers a specific output type.
     */
    public function deliversOutputType(string $type): bool
    {
        return $this->outputDataTypes !== null 
            && in_array($type, $this->outputDataTypes, true);
    }

    public function getIntegrationPoints(): ?array
    {
        return $this->integrationPoints;
    }

    public function setIntegrationPoints(?array $integrationPoints): static
    {
        $this->integrationPoints = $integrationPoints;
        return $this;
    }

    /**
     * Check if results integrate at a specific point.
     */
    public function integratesAt(string $point): bool
    {
        return $this->integrationPoints !== null 
            && in_array($point, $this->integrationPoints, true);
    }

    public function getRelevantPhases(): ?array
    {
        return $this->relevantPhases;
    }

    public function setRelevantPhases(?array $relevantPhases): static
    {
        $this->relevantPhases = $relevantPhases;
        return $this;
    }

    /**
     * Check if this offering is relevant for a specific BGM phase (1-6).
     */
    public function isRelevantForPhase(int $phase): bool
    {
        return $this->relevantPhases !== null 
            && in_array($phase, $this->relevantPhases, true);
    }

    public function isOrchestratorService(): bool
    {
        return $this->isOrchestratorService;
    }

    public function setIsOrchestratorService(bool $isOrchestratorService): static
    {
        $this->isOrchestratorService = $isOrchestratorService;
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

    /**
     * @return Collection<int, ServiceInquiry>
     */
    public function getInquiries(): Collection
    {
        return $this->inquiries;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'providerId' => $this->provider?->getId(),
            'title' => $this->title,
            'description' => $this->description,
            'pricingInfo' => $this->pricingInfo,
            'deliveryModes' => $this->deliveryModes,
            'isCertified' => $this->isCertified,
            'certificationName' => $this->certificationName,
            'duration' => $this->duration,
            'minParticipants' => $this->minParticipants,
            'maxParticipants' => $this->maxParticipants,
            'isActive' => $this->isActive,
            'sortOrder' => $this->sortOrder,
            // Partner Integration fields
            'requiredDataScopes' => $this->requiredDataScopes,
            'outputDataTypes' => $this->outputDataTypes,
            'integrationPoints' => $this->integrationPoints,
            'relevantPhases' => $this->relevantPhases,
            'isOrchestratorService' => $this->isOrchestratorService,
            'createdAt' => $this->createdAt?->format('c'),
            'updatedAt' => $this->updatedAt?->format('c'),
        ];
    }
}


