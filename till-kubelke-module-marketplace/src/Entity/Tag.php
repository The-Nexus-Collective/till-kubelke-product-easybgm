<?php

namespace TillKubelke\ModuleMarketplace\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Tag Entity - Fine-grained service tags for BGM providers.
 * 
 * Examples: Achtsamkeit, Bewegte Pause, BGM-Beratung, Check-Ups, Coaching,
 * Digitales BGM, EAP, Entspannung, Firmenfitness, GB Psych,
 * Gesund führen, Gesundheitstage, Rückengesundheit, Resilienz,
 * Teambuilding, Workshops, etc.
 */
#[ORM\Entity(repositoryClass: \TillKubelke\ModuleMarketplace\Repository\TagRepository::class)]
#[ORM\Table(name: 'marketplace_tags')]
class Tag
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private string $name;

    #[ORM\Column(type: Types::STRING, length: 100, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private string $slug;

    /**
     * @var Collection<int, ServiceProvider>
     */
    #[ORM\ManyToMany(targetEntity: ServiceProvider::class, mappedBy: 'tags')]
    private Collection $providers;

    public function __construct()
    {
        $this->providers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;
        return $this;
    }

    /**
     * @return Collection<int, ServiceProvider>
     */
    public function getProviders(): Collection
    {
        return $this->providers;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
        ];
    }
}

