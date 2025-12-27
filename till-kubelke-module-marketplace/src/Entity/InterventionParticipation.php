<?php

namespace TillKubelke\ModuleMarketplace\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use TillKubelke\ModuleMarketplace\Repository\InterventionParticipationRepository;
use TillKubelke\PlatformFoundation\Tenant\Entity\Tenant;

/**
 * InterventionParticipation Entity - Documents employee participation in BGM interventions.
 * 
 * This entity tracks who participated in what intervention, with the key principle:
 * - Personal data (names, emails) stays INTERNAL
 * - Partners only receive aggregated/anonymous statistics
 * 
 * Use cases:
 * - Track attendance at Lunch & Learn events
 * - Document participation in partner-delivered programs
 * - Generate reports for health insurance documentation
 * - Enable long-term participation analysis
 */
#[ORM\Entity(repositoryClass: InterventionParticipationRepository::class)]
#[ORM\Table(name: 'marketplace_intervention_participations')]
#[ORM\Index(name: 'idx_participation_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_participation_engagement', columns: ['engagement_id'])]
#[ORM\Index(name: 'idx_participation_date', columns: ['event_date'])]
#[ORM\HasLifecycleCallbacks]
class InterventionParticipation
{
    public const STATUS_REGISTERED = 'registered';
    public const STATUS_ATTENDED = 'attended';
    public const STATUS_NO_SHOW = 'no_show';
    public const STATUS_CANCELLED = 'cancelled';

    public const VALID_STATUSES = [
        self::STATUS_REGISTERED,
        self::STATUS_ATTENDED,
        self::STATUS_NO_SHOW,
        self::STATUS_CANCELLED,
    ];

    // Intervention types
    public const TYPE_PARTNER_ENGAGEMENT = 'partner_engagement';
    public const TYPE_HEALTH_DAY_MODULE = 'health_day_module';
    public const TYPE_INTERNAL = 'internal';

    // Categories (matching primary prevention areas)
    public const CATEGORY_BEWEGUNG = 'bewegung';
    public const CATEGORY_ERNAEHRUNG = 'ernaehrung';
    public const CATEGORY_MENTAL = 'mental';
    public const CATEGORY_SUCHT = 'sucht';
    public const CATEGORY_ERGONOMIE = 'ergonomie';
    public const CATEGORY_ALLGEMEIN = 'allgemein';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Tenant::class)]
    #[ORM\JoinColumn(name: 'tenant_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?Tenant $tenant = null;

    // ========== Intervention Reference ==========

    /**
     * Type of intervention: partner_engagement, health_day_module, or internal.
     */
    #[ORM\Column(type: Types::STRING, length: 30)]
    private string $interventionType = self::TYPE_PARTNER_ENGAGEMENT;

    /**
     * Reference to partner engagement (if type = partner_engagement).
     */
    #[ORM\ManyToOne(targetEntity: PartnerEngagement::class)]
    #[ORM\JoinColumn(name: 'engagement_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?PartnerEngagement $engagement = null;

    /**
     * For internal interventions or health day modules without engagement.
     */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $interventionTitle = null;

    /**
     * Brief description of the intervention.
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $interventionDescription = null;

    // ========== Employee Data (INTERNAL ONLY!) ==========

    /**
     * Employee ID from HR integration (if available).
     * Note: This is an internal reference, never shared with partners.
     */
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $employeeId = null;

    /**
     * Employee email (for cases without HR integration).
     * Note: NEVER shared with partners - only for internal tracking.
     */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Assert\Email]
    private ?string $employeeEmail = null;

    /**
     * Employee name for display purposes.
     * Note: NEVER shared with partners.
     */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $employeeName = null;

    /**
     * Department/team for aggregated reporting.
     */
    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $department = null;

    // ========== Event Details ==========

    /**
     * Date of the event/intervention.
     */
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $eventDate = null;

    /**
     * Category for reporting (bewegung, ernaehrung, mental, etc.).
     */
    #[ORM\Column(type: Types::STRING, length: 30, nullable: true)]
    private ?string $category = null;

    // ========== Participation Status ==========

    #[ORM\Column(type: Types::STRING, length: 20, options: ['default' => 'registered'])]
    private string $status = self::STATUS_REGISTERED;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $registeredAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $attendedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $cancelledAt = null;

    // ========== Feedback (Optional) ==========

    /**
     * Rating 1-5 stars.
     */
    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Assert\Range(min: 1, max: 5)]
    private ?int $rating = null;

    /**
     * Feedback comment from participant.
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $feedbackComment = null;

    // ========== Special Requirements ==========

    /**
     * Dietary or other requirements (for events with catering).
     * e.g. ["vegan", "gluten_free"]
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $specialRequirements = null;

    // ========== Timestamps ==========

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->registeredAt = new \DateTimeImmutable();
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

    // ========== Tenant ==========

    public function getTenant(): ?Tenant
    {
        return $this->tenant;
    }

    public function setTenant(?Tenant $tenant): static
    {
        $this->tenant = $tenant;
        return $this;
    }

    // ========== Intervention Reference ==========

    public function getInterventionType(): string
    {
        return $this->interventionType;
    }

    public function setInterventionType(string $interventionType): static
    {
        $this->interventionType = $interventionType;
        return $this;
    }

    public function getEngagement(): ?PartnerEngagement
    {
        return $this->engagement;
    }

    public function setEngagement(?PartnerEngagement $engagement): static
    {
        $this->engagement = $engagement;
        if ($engagement !== null) {
            $this->interventionType = self::TYPE_PARTNER_ENGAGEMENT;
        }
        return $this;
    }

    public function getInterventionTitle(): ?string
    {
        // If linked to engagement, get title from offering
        if ($this->engagement !== null) {
            return $this->engagement->getOffering()?->getTitle();
        }
        return $this->interventionTitle;
    }

    public function setInterventionTitle(?string $interventionTitle): static
    {
        $this->interventionTitle = $interventionTitle;
        return $this;
    }

    public function getInterventionDescription(): ?string
    {
        return $this->interventionDescription;
    }

    public function setInterventionDescription(?string $interventionDescription): static
    {
        $this->interventionDescription = $interventionDescription;
        return $this;
    }

    // ========== Employee Data ==========

    public function getEmployeeId(): ?int
    {
        return $this->employeeId;
    }

    public function setEmployeeId(?int $employeeId): static
    {
        $this->employeeId = $employeeId;
        return $this;
    }

    public function getEmployeeEmail(): ?string
    {
        return $this->employeeEmail;
    }

    public function setEmployeeEmail(?string $employeeEmail): static
    {
        $this->employeeEmail = $employeeEmail;
        return $this;
    }

    public function getEmployeeName(): ?string
    {
        return $this->employeeName;
    }

    public function setEmployeeName(?string $employeeName): static
    {
        $this->employeeName = $employeeName;
        return $this;
    }

    public function getDepartment(): ?string
    {
        return $this->department;
    }

    public function setDepartment(?string $department): static
    {
        $this->department = $department;
        return $this;
    }

    // ========== Event Details ==========

    public function getEventDate(): ?\DateTimeInterface
    {
        return $this->eventDate;
    }

    public function setEventDate(?\DateTimeInterface $eventDate): static
    {
        $this->eventDate = $eventDate;
        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): static
    {
        $this->category = $category;
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

    public function isRegistered(): bool
    {
        return $this->status === self::STATUS_REGISTERED;
    }

    public function isAttended(): bool
    {
        return $this->status === self::STATUS_ATTENDED;
    }

    public function isNoShow(): bool
    {
        return $this->status === self::STATUS_NO_SHOW;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function markAttended(): static
    {
        $this->status = self::STATUS_ATTENDED;
        $this->attendedAt = new \DateTimeImmutable();
        return $this;
    }

    public function markNoShow(): static
    {
        $this->status = self::STATUS_NO_SHOW;
        return $this;
    }

    public function markCancelled(): static
    {
        $this->status = self::STATUS_CANCELLED;
        $this->cancelledAt = new \DateTimeImmutable();
        return $this;
    }

    public function getRegisteredAt(): ?\DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function getAttendedAt(): ?\DateTimeImmutable
    {
        return $this->attendedAt;
    }

    public function getCancelledAt(): ?\DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    // ========== Feedback ==========

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function setRating(?int $rating): static
    {
        if ($rating !== null && ($rating < 1 || $rating > 5)) {
            throw new \InvalidArgumentException('Rating must be between 1 and 5');
        }
        $this->rating = $rating;
        return $this;
    }

    public function getFeedbackComment(): ?string
    {
        return $this->feedbackComment;
    }

    public function setFeedbackComment(?string $feedbackComment): static
    {
        $this->feedbackComment = $feedbackComment;
        return $this;
    }

    public function hasFeedback(): bool
    {
        return $this->rating !== null || $this->feedbackComment !== null;
    }

    // ========== Special Requirements ==========

    public function getSpecialRequirements(): ?array
    {
        return $this->specialRequirements;
    }

    public function setSpecialRequirements(?array $specialRequirements): static
    {
        $this->specialRequirements = $specialRequirements;
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

    // ========== Serialization ==========

    /**
     * Convert to array for internal use (includes personal data).
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenantId' => $this->tenant?->getId(),
            'interventionType' => $this->interventionType,
            'engagementId' => $this->engagement?->getId(),
            'interventionTitle' => $this->getInterventionTitle(),
            'employeeId' => $this->employeeId,
            'employeeEmail' => $this->employeeEmail,
            'employeeName' => $this->employeeName,
            'department' => $this->department,
            'eventDate' => $this->eventDate?->format('Y-m-d'),
            'category' => $this->category,
            'status' => $this->status,
            'rating' => $this->rating,
            'feedbackComment' => $this->feedbackComment,
            'specialRequirements' => $this->specialRequirements,
            'registeredAt' => $this->registeredAt?->format('c'),
            'attendedAt' => $this->attendedAt?->format('c'),
            'createdAt' => $this->createdAt?->format('c'),
        ];
    }

    /**
     * Convert to anonymized array for partner sharing (NO personal data!).
     */
    public function toAnonymizedArray(): array
    {
        return [
            'id' => $this->id,
            'interventionType' => $this->interventionType,
            'eventDate' => $this->eventDate?->format('Y-m-d'),
            'category' => $this->category,
            'status' => $this->status,
            'hasFeedback' => $this->hasFeedback(),
            'rating' => $this->rating, // Rating is ok to share (anonymous)
            // NO: employeeId, employeeEmail, employeeName, feedbackComment
        ];
    }
}







