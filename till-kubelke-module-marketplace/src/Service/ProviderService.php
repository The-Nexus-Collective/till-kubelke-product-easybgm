<?php

namespace TillKubelke\ModuleMarketplace\Service;

use Doctrine\ORM\EntityManagerInterface;
use TillKubelke\ModuleMarketplace\Entity\Category;
use TillKubelke\ModuleMarketplace\Entity\ServiceOffering;
use TillKubelke\ModuleMarketplace\Entity\ServiceProvider;
use TillKubelke\ModuleMarketplace\Entity\Tag;
use TillKubelke\ModuleMarketplace\Repository\CategoryRepository;
use TillKubelke\ModuleMarketplace\Repository\ServiceProviderRepository;
use TillKubelke\ModuleMarketplace\Repository\TagRepository;
use TillKubelke\PlatformFoundation\Auth\Entity\User;

class ProviderService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ServiceProviderRepository $providerRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly TagRepository $tagRepository,
    ) {
    }

    /**
     * Register a new service provider (pending approval).
     *
     * @param array $data Provider data
     * @param User|null $owner User who will own/manage this provider profile
     */
    public function register(array $data, ?User $owner = null): ServiceProvider
    {
        $provider = new ServiceProvider();
        $provider->setCompanyName($data['companyName']);
        $provider->setContactEmail($data['contactEmail']);
        $provider->setContactPhone($data['contactPhone'] ?? null);
        $provider->setContactPerson($data['contactPerson'] ?? null);
        $provider->setDescription($data['description']);
        $provider->setShortDescription($data['shortDescription'] ?? null);
        $provider->setLogoUrl($data['logoUrl'] ?? null);
        $provider->setWebsite($data['website'] ?? null);
        $provider->setLocation($data['location'] ?? null);
        $provider->setServiceRegions($data['serviceRegions'] ?? null);
        $provider->setIsNationwide($data['isNationwide'] ?? false);
        $provider->setOffersRemote($data['offersRemote'] ?? false);

        // Set owner FIRST, before any other operations
        // This ensures Doctrine tracks the relationship from the start
        if ($owner !== null) {
            // Always reload owner from database to ensure it's managed by EntityManager
            $ownerId = $owner->getId();
            $managedOwner = $this->entityManager->find(User::class, $ownerId);
            if ($managedOwner === null) {
                throw new \RuntimeException(sprintf('Owner user with ID %d not found in database', $ownerId));
            }
            $provider->setOwner($managedOwner);
        }

        // Add categories
        if (!empty($data['categoryIds'])) {
            foreach ($data['categoryIds'] as $categoryId) {
                $category = $this->categoryRepository->find($categoryId);
                if ($category) {
                    $provider->addCategory($category);
                }
            }
        }

        // Add tags
        if (!empty($data['tagIds'])) {
            foreach ($data['tagIds'] as $tagId) {
                $tag = $this->tagRepository->find($tagId);
                if ($tag) {
                    $provider->addTag($tag);
                }
            }
        }

        // Add offerings if provided
        if (!empty($data['offerings'])) {
            foreach ($data['offerings'] as $offeringData) {
                $offering = $this->createOffering($offeringData);
                $provider->addOffering($offering);
            }
        }

        // Persist and flush
        $this->entityManager->persist($provider);
        $this->entityManager->flush();
        
        // Verify owner was saved (with workaround if needed)
        if ($owner !== null) {
            $this->verifyOwnerSaved($provider, $owner);
        }

        return $provider;
    }

    /**
     * Verify that owner was saved to database.
     * This method always updates owner_id directly in the database as a workaround
     * for a Doctrine issue where the relationship is not persisted correctly.
     */
    private function verifyOwnerSaved(ServiceProvider $provider, User $owner): void
    {
        $connection = $this->entityManager->getConnection();
        $providerId = $provider->getId();
        $ownerId = $owner->getId();
        
        // Always update directly in database to ensure owner_id is set
        // This is a workaround for a Doctrine persistence issue
        $connection->executeStatement(
            'UPDATE marketplace_service_providers SET owner_id = ? WHERE id = ?',
            [$ownerId, $providerId],
            [\PDO::PARAM_INT, \PDO::PARAM_INT]
        );
        
        // Verify it was saved
        $savedOwnerId = $connection->fetchOne(
            'SELECT owner_id FROM marketplace_service_providers WHERE id = ?',
            [$providerId]
        );
        
        if ($savedOwnerId !== (string)$ownerId && $savedOwnerId !== $ownerId && $savedOwnerId !== null) {
            throw new \RuntimeException(
                sprintf(
                    'Failed to save owner_id via direct SQL update. Expected: %s, Got: %s, Provider ID: %s',
                    $ownerId,
                    $savedOwnerId ?? 'NULL',
                    $providerId
                )
            );
        }
        
        // Refresh provider to load the updated owner_id
        $this->entityManager->refresh($provider);
    }

    /**
     * Update a service provider.
     */
    public function update(ServiceProvider $provider, array $data): ServiceProvider
    {
        if (isset($data['companyName'])) {
            $provider->setCompanyName($data['companyName']);
        }
        if (isset($data['contactEmail'])) {
            $provider->setContactEmail($data['contactEmail']);
        }
        if (array_key_exists('contactPhone', $data)) {
            $provider->setContactPhone($data['contactPhone']);
        }
        if (array_key_exists('contactPerson', $data)) {
            $provider->setContactPerson($data['contactPerson']);
        }
        if (isset($data['description'])) {
            $provider->setDescription($data['description']);
        }
        if (array_key_exists('shortDescription', $data)) {
            $provider->setShortDescription($data['shortDescription']);
        }
        if (array_key_exists('logoUrl', $data)) {
            $provider->setLogoUrl($data['logoUrl']);
        }
        if (array_key_exists('website', $data)) {
            $provider->setWebsite($data['website']);
        }
        if (array_key_exists('location', $data)) {
            $provider->setLocation($data['location']);
        }
        if (array_key_exists('serviceRegions', $data)) {
            $provider->setServiceRegions($data['serviceRegions']);
        }
        if (isset($data['isNationwide'])) {
            $provider->setIsNationwide($data['isNationwide']);
        }
        if (isset($data['offersRemote'])) {
            $provider->setOffersRemote($data['offersRemote']);
        }

        // Update categories
        if (isset($data['categoryIds'])) {
            // Clear existing categories
            foreach ($provider->getCategories() as $category) {
                $provider->removeCategory($category);
            }
            // Add new categories
            foreach ($data['categoryIds'] as $categoryId) {
                $category = $this->categoryRepository->find($categoryId);
                if ($category) {
                    $provider->addCategory($category);
                }
            }
        }

        // Update tags
        if (isset($data['tagIds'])) {
            // Clear existing tags
            foreach ($provider->getTags() as $tag) {
                $provider->removeTag($tag);
            }
            // Add new tags
            foreach ($data['tagIds'] as $tagId) {
                $tag = $this->tagRepository->find($tagId);
                if ($tag) {
                    $provider->addTag($tag);
                }
            }
        }

        $this->entityManager->flush();

        return $provider;
    }

    /**
     * Approve a pending provider.
     */
    public function approve(ServiceProvider $provider): ServiceProvider
    {
        $provider->approve();
        $this->entityManager->flush();

        return $provider;
    }

    /**
     * Reject a pending provider.
     */
    public function reject(ServiceProvider $provider, string $reason): ServiceProvider
    {
        $provider->reject($reason);
        $this->entityManager->flush();

        return $provider;
    }

    /**
     * Add an offering to a provider.
     */
    public function addOffering(ServiceProvider $provider, array $data): ServiceOffering
    {
        $offering = $this->createOffering($data);
        $provider->addOffering($offering);

        $this->entityManager->persist($offering);
        $this->entityManager->flush();

        return $offering;
    }

    /**
     * Update an offering.
     */
    public function updateOffering(ServiceOffering $offering, array $data): ServiceOffering
    {
        if (isset($data['title'])) {
            $offering->setTitle($data['title']);
        }
        if (isset($data['description'])) {
            $offering->setDescription($data['description']);
        }
        if (array_key_exists('pricingInfo', $data)) {
            $offering->setPricingInfo($data['pricingInfo']);
        }
        if (isset($data['deliveryModes'])) {
            $offering->setDeliveryModes($data['deliveryModes']);
        }
        if (isset($data['isCertified'])) {
            $offering->setIsCertified($data['isCertified']);
        }
        if (array_key_exists('certificationName', $data)) {
            $offering->setCertificationName($data['certificationName']);
        }
        if (array_key_exists('duration', $data)) {
            $offering->setDuration($data['duration']);
        }
        if (array_key_exists('minParticipants', $data)) {
            $offering->setMinParticipants($data['minParticipants']);
        }
        if (array_key_exists('maxParticipants', $data)) {
            $offering->setMaxParticipants($data['maxParticipants']);
        }
        if (isset($data['isActive'])) {
            $offering->setIsActive($data['isActive']);
        }
        if (isset($data['sortOrder'])) {
            $offering->setSortOrder($data['sortOrder']);
        }

        $this->entityManager->flush();

        return $offering;
    }

    /**
     * Remove an offering.
     */
    public function removeOffering(ServiceOffering $offering): void
    {
        $this->entityManager->remove($offering);
        $this->entityManager->flush();
    }

    /**
     * Find the provider profile owned by a user.
     */
    public function findByOwner(User $user): ?ServiceProvider
    {
        return $this->providerRepository->findOneBy(['owner' => $user]);
    }

    /**
     * Check if a user has a provider profile.
     */
    public function hasProviderProfile(User $user): bool
    {
        return $this->findByOwner($user) !== null;
    }

    private function createOffering(array $data): ServiceOffering
    {
        $offering = new ServiceOffering();
        $offering->setTitle($data['title']);
        $offering->setDescription($data['description']);
        $offering->setPricingInfo($data['pricingInfo'] ?? null);
        $offering->setDeliveryModes($data['deliveryModes'] ?? [ServiceOffering::DELIVERY_ONSITE]);
        $offering->setIsCertified($data['isCertified'] ?? false);
        $offering->setCertificationName($data['certificationName'] ?? null);
        $offering->setDuration($data['duration'] ?? null);
        $offering->setMinParticipants($data['minParticipants'] ?? null);
        $offering->setMaxParticipants($data['maxParticipants'] ?? null);
        $offering->setIsActive($data['isActive'] ?? true);
        $offering->setSortOrder($data['sortOrder'] ?? 0);

        return $offering;
    }
}

