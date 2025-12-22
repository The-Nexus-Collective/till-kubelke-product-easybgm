<?php

namespace TillKubelke\ModuleMarketplace\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use TillKubelke\ModuleMarketplace\Repository\PartnerReviewRepository;
use TillKubelke\PlatformFoundation\Auth\Entity\User;
use TillKubelke\PlatformFoundation\Tenant\Entity\Tenant;

/**
 * PartnerReview Entity - Customer reviews of service providers.
 * 
 * Allows tenants to rate and review service providers after completing
 * an engagement. Reviews are public (anonymous) but tied to an engagement
 * for verification.
 */
#[ORM\Entity(repositoryClass: PartnerReviewRepository::class)]
#[ORM\Table(name: 'marketplace_partner_reviews')]
#[ORM\UniqueConstraint(name: 'engagement_unique', columns: ['engagement_id'])]
#[ORM\Index(name: 'idx_provider_reviews', columns: ['provider_id'])]
#[ORM\Index(name: 'idx_tenant_reviews', columns: ['tenant_id'])]
#[ORM\HasLifecycleCallbacks]
class PartnerReview
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Tenant::class)]
    #[ORM\JoinColumn(name: 'tenant_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private Tenant $tenant;

    #[ORM\ManyToOne(targetEntity: ServiceProvider::class)]
    #[ORM\JoinColumn(name: 'provider_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ServiceProvider $provider;

    /**
     * The engagement this review is based on (for verification).
     */
    #[ORM\OneToOne(targetEntity: PartnerEngagement::class)]
    #[ORM\JoinColumn(name: 'engagement_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?PartnerEngagement $engagement = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'author_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $author = null;

    // ========== Rating Fields ==========

    /**
     * Overall rating (1-5 stars).
     */
    #[ORM\Column(type: Types::SMALLINT)]
    #[Assert\Range(min: 1, max: 5)]
    private int $overallRating;

    /**
     * Kommunikation rating (1-5 stars).
     */
    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Assert\Range(min: 1, max: 5)]
    private ?int $communicationRating = null;

    /**
     * Qualität rating (1-5 stars).
     */
    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Assert\Range(min: 1, max: 5)]
    private ?int $qualityRating = null;

    /**
     * Preis-Leistung rating (1-5 stars).
     */
    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Assert\Range(min: 1, max: 5)]
    private ?int $valueRating = null;

    /**
     * Zuverlässigkeit rating (1-5 stars).
     */
    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Assert\Range(min: 1, max: 5)]
    private ?int $reliabilityRating = null;

    // ========== Text Content ==========

    /**
     * Review title/headline.
     */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $title = null;

    /**
     * Review text/comment.
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 5000)]
    private ?string $comment = null;

    /**
     * Pros (comma-separated or array).
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $pros = null;

    /**
     * Cons (comma-separated or array).
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $cons = null;

    // ========== Meta ==========

    /**
     * Service type used (for context).
     */
    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $serviceUsed = null;

    /**
     * Would recommend to others?
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $wouldRecommend = true;

    /**
     * Display the company name? (Or anonymous)
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $showCompanyName = false;

    /**
     * Review status (pending, approved, rejected).
     */
    #[ORM\Column(type: Types::STRING, length: 20, options: ['default' => 'pending'])]
    private string $status = self::STATUS_PENDING;

    /**
     * Admin rejection reason.
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $rejectionReason = null;

    // ========== Timestamps ==========

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $approvedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = $this->createdAt ?? new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // ========== Getters / Setters ==========

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

    public function getEngagement(): ?PartnerEngagement
    {
        return $this->engagement;
    }

    public function setEngagement(?PartnerEngagement $engagement): static
    {
        $this->engagement = $engagement;
        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;
        return $this;
    }

    public function getOverallRating(): int
    {
        return $this->overallRating;
    }

    public function setOverallRating(int $overallRating): static
    {
        $this->overallRating = max(1, min(5, $overallRating));
        return $this;
    }

    public function getCommunicationRating(): ?int
    {
        return $this->communicationRating;
    }

    public function setCommunicationRating(?int $communicationRating): static
    {
        $this->communicationRating = $communicationRating ? max(1, min(5, $communicationRating)) : null;
        return $this;
    }

    public function getQualityRating(): ?int
    {
        return $this->qualityRating;
    }

    public function setQualityRating(?int $qualityRating): static
    {
        $this->qualityRating = $qualityRating ? max(1, min(5, $qualityRating)) : null;
        return $this;
    }

    public function getValueRating(): ?int
    {
        return $this->valueRating;
    }

    public function setValueRating(?int $valueRating): static
    {
        $this->valueRating = $valueRating ? max(1, min(5, $valueRating)) : null;
        return $this;
    }

    public function getReliabilityRating(): ?int
    {
        return $this->reliabilityRating;
    }

    public function setReliabilityRating(?int $reliabilityRating): static
    {
        $this->reliabilityRating = $reliabilityRating ? max(1, min(5, $reliabilityRating)) : null;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;
        return $this;
    }

    public function getPros(): ?array
    {
        return $this->pros;
    }

    public function setPros(?array $pros): static
    {
        $this->pros = $pros;
        return $this;
    }

    public function getCons(): ?array
    {
        return $this->cons;
    }

    public function setCons(?array $cons): static
    {
        $this->cons = $cons;
        return $this;
    }

    public function getServiceUsed(): ?string
    {
        return $this->serviceUsed;
    }

    public function setServiceUsed(?string $serviceUsed): static
    {
        $this->serviceUsed = $serviceUsed;
        return $this;
    }

    public function wouldRecommend(): bool
    {
        return $this->wouldRecommend;
    }

    public function setWouldRecommend(bool $wouldRecommend): static
    {
        $this->wouldRecommend = $wouldRecommend;
        return $this;
    }

    public function showCompanyName(): bool
    {
        return $this->showCompanyName;
    }

    public function setShowCompanyName(bool $showCompanyName): static
    {
        $this->showCompanyName = $showCompanyName;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function approve(): static
    {
        $this->status = self::STATUS_APPROVED;
        $this->approvedAt = new \DateTimeImmutable();
        $this->rejectionReason = null;
        return $this;
    }

    public function reject(string $reason): static
    {
        $this->status = self::STATUS_REJECTED;
        $this->rejectionReason = $reason;
        return $this;
    }

    public function getRejectionReason(): ?string
    {
        return $this->rejectionReason;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getApprovedAt(): ?\DateTimeImmutable
    {
        return $this->approvedAt;
    }

    // ========== Computed ==========

    /**
     * Get average of all sub-ratings.
     */
    public function getAverageSubRating(): ?float
    {
        $ratings = array_filter([
            $this->communicationRating,
            $this->qualityRating,
            $this->valueRating,
            $this->reliabilityRating,
        ], fn($r) => $r !== null);

        if (empty($ratings)) {
            return null;
        }

        return round(array_sum($ratings) / count($ratings), 1);
    }

    /**
     * Get reviewer display name.
     */
    public function getReviewerName(): string
    {
        if ($this->showCompanyName && $this->tenant) {
            return $this->tenant->getName();
        }
        return 'Verifizierter Kunde';
    }

    // ========== Serialization ==========

    public function toArray(bool $includePrivate = false): array
    {
        $data = [
            'id' => $this->id,
            'providerId' => $this->provider->getId(),
            'providerName' => $this->provider->getCompanyName(),
            'overallRating' => $this->overallRating,
            'communicationRating' => $this->communicationRating,
            'qualityRating' => $this->qualityRating,
            'valueRating' => $this->valueRating,
            'reliabilityRating' => $this->reliabilityRating,
            'averageSubRating' => $this->getAverageSubRating(),
            'title' => $this->title,
            'comment' => $this->comment,
            'pros' => $this->pros,
            'cons' => $this->cons,
            'serviceUsed' => $this->serviceUsed,
            'wouldRecommend' => $this->wouldRecommend,
            'reviewerName' => $this->getReviewerName(),
            'isVerified' => $this->engagement !== null,
            'status' => $this->status,
            'createdAt' => $this->createdAt?->format('c'),
        ];

        if ($includePrivate) {
            $data['tenantId'] = $this->tenant->getId();
            $data['engagementId'] = $this->engagement?->getId();
            $data['authorId'] = $this->author?->getId();
            $data['showCompanyName'] = $this->showCompanyName;
            $data['rejectionReason'] = $this->rejectionReason;
            $data['updatedAt'] = $this->updatedAt?->format('c');
            $data['approvedAt'] = $this->approvedAt?->format('c');
        }

        return $data;
    }
}




