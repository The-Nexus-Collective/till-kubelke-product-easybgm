<?php

namespace TillKubelke\ModuleMarketplace\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use TillKubelke\ModuleMarketplace\Repository\ServiceProviderRepository;
use TillKubelke\PlatformFoundation\Auth\Entity\User;
use TillKubelke\PlatformFoundation\Geo\Entity\Market;

/**
 * ServiceProvider Entity - BGM service provider companies.
 * 
 * Represents companies that offer BGM (Betriebliches Gesundheitsmanagement)
 * services to tenants on the platform.
 */
#[ORM\Entity(repositoryClass: ServiceProviderRepository::class)]
#[ORM\Table(name: 'marketplace_service_providers')]
#[ORM\HasLifecycleCallbacks]
class ServiceProvider
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $companyName;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private string $contactEmail;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $contactPhone = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $contactPerson = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 50, max: 5000)]
    private string $description;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $shortDescription = null;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    #[Assert\Url]
    private ?string $logoUrl = null;

    /**
     * Cover/header image for the provider card.
     */
    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    #[Assert\Url]
    private ?string $coverImageUrl = null;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    #[Assert\Url]
    private ?string $website = null;

    #[ORM\Column(type: Types::STRING, length: 20, options: ['default' => 'pending'])]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $location = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $serviceRegions = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isNationwide = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $offersRemote = false;

    /**
     * Premium partners get special highlighting and priority placement.
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isPremium = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $approvedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $rejectionReason = null;

    /**
     * The user who owns/manages this provider profile.
     * Can be null for legacy providers or pending registrations.
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'owner_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $owner = null;

    /**
     * @var Collection<int, Category>
     */
    #[ORM\ManyToMany(targetEntity: Category::class, inversedBy: 'providers')]
    #[ORM\JoinTable(name: 'marketplace_provider_categories')]
    private Collection $categories;

    /**
     * @var Collection<int, Tag>
     */
    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'providers')]
    #[ORM\JoinTable(name: 'marketplace_provider_tags')]
    private Collection $tags;

    /**
     * @var Collection<int, ServiceOffering>
     */
    #[ORM\OneToMany(mappedBy: 'provider', targetEntity: ServiceOffering::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $offerings;

    /**
     * @var Collection<int, ServiceInquiry>
     */
    #[ORM\OneToMany(mappedBy: 'provider', targetEntity: ServiceInquiry::class)]
    private Collection $inquiries;

    /**
     * Markets where this provider offers services (country-level).
     * This is different from serviceRegions which are sub-regions within a country (e.g., German Bundesländer).
     * 
     * @var Collection<int, Market>
     */
    #[ORM\ManyToMany(targetEntity: Market::class)]
    #[ORM\JoinTable(
        name: 'marketplace_provider_markets',
        joinColumns: [new ORM\JoinColumn(name: 'provider_id', referencedColumnName: 'id')],
        inverseJoinColumns: [new ORM\JoinColumn(name: 'market_code', referencedColumnName: 'code')]
    )]
    private Collection $markets;

    // ========== Cached Rating Stats (set externally) ==========
    
    /**
     * Cached average rating (not persisted, set by service).
     */
    private ?float $cachedAverageRating = null;

    /**
     * Cached review count (not persisted, set by service).
     */
    private ?int $cachedReviewCount = null;

    /**
     * Cached recommend rate (not persisted, set by service).
     */
    private ?int $cachedRecommendRate = null;

    public function __construct()
    {
        $this->categories = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->offerings = new ArrayCollection();
        $this->inquiries = new ArrayCollection();
        $this->markets = new ArrayCollection();
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

    public function getCompanyName(): string
    {
        return $this->companyName;
    }

    public function setCompanyName(string $companyName): static
    {
        $this->companyName = $companyName;
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

    public function getContactPerson(): ?string
    {
        return $this->contactPerson;
    }

    public function setContactPerson(?string $contactPerson): static
    {
        $this->contactPerson = $contactPerson;
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

    public function getShortDescription(): ?string
    {
        return $this->shortDescription;
    }

    public function setShortDescription(?string $shortDescription): static
    {
        $this->shortDescription = $shortDescription;
        return $this;
    }

    public function getLogoUrl(): ?string
    {
        return $this->logoUrl;
    }

    public function setLogoUrl(?string $logoUrl): static
    {
        $this->logoUrl = $logoUrl;
        return $this;
    }

    public function getCoverImageUrl(): ?string
    {
        return $this->coverImageUrl;
    }

    public function setCoverImageUrl(?string $coverImageUrl): static
    {
        $this->coverImageUrl = $coverImageUrl;
        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): static
    {
        $this->website = $website;
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
        $this->approvedAt = null;
        return $this;
    }

    public function getLocation(): ?array
    {
        return $this->location;
    }

    public function setLocation(?array $location): static
    {
        $this->location = $location;
        return $this;
    }

    public function getServiceRegions(): ?array
    {
        return $this->serviceRegions;
    }

    public function setServiceRegions(?array $serviceRegions): static
    {
        $this->serviceRegions = $serviceRegions;
        return $this;
    }

    public function isNationwide(): bool
    {
        return $this->isNationwide;
    }

    public function setIsNationwide(bool $isNationwide): static
    {
        $this->isNationwide = $isNationwide;
        return $this;
    }

    public function offersRemote(): bool
    {
        return $this->offersRemote;
    }

    public function setOffersRemote(bool $offersRemote): static
    {
        $this->offersRemote = $offersRemote;
        return $this;
    }

    public function isPremium(): bool
    {
        return $this->isPremium;
    }

    public function setIsPremium(bool $isPremium): static
    {
        $this->isPremium = $isPremium;
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

    public function getApprovedAt(): ?\DateTimeImmutable
    {
        return $this->approvedAt;
    }

    public function getRejectionReason(): ?string
    {
        return $this->rejectionReason;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;
        return $this;
    }

    /**
     * Check if a user is the owner of this provider.
     */
    public function isOwnedBy(?User $user): bool
    {
        if ($this->owner === null || $user === null) {
            return false;
        }
        return $this->owner->getId() === $user->getId();
    }

    /**
     * @return Collection<int, Category>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): static
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
        }
        return $this;
    }

    public function removeCategory(Category $category): static
    {
        $this->categories->removeElement($category);
        return $this;
    }

    /**
     * @return Collection<int, Tag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): static
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }
        return $this;
    }

    public function removeTag(Tag $tag): static
    {
        $this->tags->removeElement($tag);
        return $this;
    }

    /**
     * @return Collection<int, ServiceOffering>
     */
    public function getOfferings(): Collection
    {
        return $this->offerings;
    }

    public function addOffering(ServiceOffering $offering): static
    {
        if (!$this->offerings->contains($offering)) {
            $this->offerings->add($offering);
            $offering->setProvider($this);
        }
        return $this;
    }

    public function removeOffering(ServiceOffering $offering): static
    {
        if ($this->offerings->removeElement($offering)) {
            if ($offering->getProvider() === $this) {
                $offering->setProvider(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, ServiceInquiry>
     */
    public function getInquiries(): Collection
    {
        return $this->inquiries;
    }

    // ========== Markets (Country-level) ==========

    /**
     * Get all markets where this provider operates.
     * 
     * @return Collection<int, Market>
     */
    public function getMarkets(): Collection
    {
        return $this->markets;
    }

    /**
     * Add a market where this provider operates.
     */
    public function addMarket(Market $market): static
    {
        if (!$this->markets->contains($market)) {
            $this->markets->add($market);
        }
        return $this;
    }

    /**
     * Remove a market from this provider.
     */
    public function removeMarket(Market $market): static
    {
        $this->markets->removeElement($market);
        return $this;
    }

    /**
     * Check if this provider operates in a specific market.
     */
    public function operatesInMarket(string $marketCode): bool
    {
        $marketCode = strtoupper($marketCode);
        foreach ($this->markets as $market) {
            if ($market->getCode() === $marketCode) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get all market codes as an array.
     * 
     * @return string[]
     */
    public function getMarketCodes(): array
    {
        return array_map(
            fn(Market $m) => $m->getCode(),
            $this->markets->toArray()
        );
    }

    // ========== Computed Properties ==========

    /**
     * Get all unique relevant phases from all offerings.
     * @return int[]
     */
    public function getRelevantPhases(): array
    {
        $phases = [];
        foreach ($this->offerings as $offering) {
            $offeringPhases = $offering->getRelevantPhases();
            if ($offeringPhases) {
                $phases = array_merge($phases, $offeringPhases);
            }
        }
        $phases = array_unique($phases);
        sort($phases);
        return array_values($phases);
    }

    /**
     * Check if provider has any §20 SGB V certified offerings.
     */
    public function hasCertifiedOfferings(): bool
    {
        foreach ($this->offerings as $offering) {
            if ($offering->isCertified()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get all unique certification names from offerings.
     * @return string[]
     */
    public function getCertificationNames(): array
    {
        $names = [];
        foreach ($this->offerings as $offering) {
            if ($offering->isCertified() && $offering->getCertificationName()) {
                $names[] = $offering->getCertificationName();
            }
        }
        return array_unique($names);
    }

    // ========== Rating Stats (cached, set externally) ==========

    public function getCachedAverageRating(): ?float
    {
        return $this->cachedAverageRating;
    }

    public function setCachedAverageRating(?float $rating): static
    {
        $this->cachedAverageRating = $rating;
        return $this;
    }

    public function getCachedReviewCount(): ?int
    {
        return $this->cachedReviewCount;
    }

    public function setCachedReviewCount(?int $count): static
    {
        $this->cachedReviewCount = $count;
        return $this;
    }

    public function getCachedRecommendRate(): ?int
    {
        return $this->cachedRecommendRate;
    }

    public function setCachedRecommendRate(?int $rate): static
    {
        $this->cachedRecommendRate = $rate;
        return $this;
    }

    public function toArray(bool $includeDetails = false): array
    {
        $data = [
            'id' => $this->id,
            'companyName' => $this->companyName,
            'shortDescription' => $this->shortDescription,
            'logoUrl' => $this->logoUrl,
            'coverImageUrl' => $this->coverImageUrl,
            'website' => $this->website,
            'status' => $this->status,
            'isNationwide' => $this->isNationwide,
            'offersRemote' => $this->offersRemote,
            'isPremium' => $this->isPremium,
            'categories' => array_map(fn(Category $c) => $c->toArray(), $this->categories->toArray()),
            'tags' => array_map(fn(Tag $t) => $t->toArray(), $this->tags->toArray()),
            'markets' => array_map(fn(Market $m) => $m->toArray(), $this->markets->toArray()),
            'marketCodes' => $this->getMarketCodes(),
            'createdAt' => $this->createdAt?->format('c'),
            // Computed properties for card display
            'relevantPhases' => $this->getRelevantPhases(),
            'hasCertifiedOfferings' => $this->hasCertifiedOfferings(),
            'certifications' => $this->getCertificationNames(),
            // Rating stats (populated externally)
            'averageRating' => $this->cachedAverageRating,
            'reviewCount' => $this->cachedReviewCount,
            'recommendRate' => $this->cachedRecommendRate,
        ];

        if ($includeDetails) {
            $data['contactEmail'] = $this->contactEmail;
            $data['contactPhone'] = $this->contactPhone;
            $data['contactPerson'] = $this->contactPerson;
            $data['description'] = $this->description;
            $data['location'] = $this->location;
            $data['serviceRegions'] = $this->serviceRegions;
            $data['offerings'] = array_map(fn(ServiceOffering $o) => $o->toArray(), $this->offerings->toArray());
            $data['approvedAt'] = $this->approvedAt?->format('c');
            $data['updatedAt'] = $this->updatedAt?->format('c');
            $data['ownerId'] = $this->owner?->getId();
        }

        return $data;
    }
}

