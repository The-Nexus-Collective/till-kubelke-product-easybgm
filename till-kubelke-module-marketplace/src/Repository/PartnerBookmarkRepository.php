<?php

namespace TillKubelke\ModuleMarketplace\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use TillKubelke\ModuleMarketplace\Entity\PartnerBookmark;
use TillKubelke\PlatformFoundation\Tenant\Entity\Tenant;

/**
 * @extends ServiceEntityRepository<PartnerBookmark>
 */
class PartnerBookmarkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PartnerBookmark::class);
    }

    /**
     * Find all bookmarks for a tenant.
     * 
     * @return PartnerBookmark[]
     */
    public function findByTenant(Tenant $tenant): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.tenant = :tenant')
            ->setParameter('tenant', $tenant)
            ->orderBy('b.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find a specific bookmark.
     */
    public function findOneByTenantAndProvider(Tenant $tenant, int $providerId): ?PartnerBookmark
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.tenant = :tenant')
            ->andWhere('b.provider = :providerId')
            ->setParameter('tenant', $tenant)
            ->setParameter('providerId', $providerId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get all provider IDs that are bookmarked by a tenant.
     * 
     * @return int[]
     */
    public function getBookmarkedProviderIds(Tenant $tenant): array
    {
        $result = $this->createQueryBuilder('b')
            ->select('IDENTITY(b.provider) as providerId')
            ->andWhere('b.tenant = :tenant')
            ->setParameter('tenant', $tenant)
            ->getQuery()
            ->getScalarResult();

        return array_map(fn($row) => (int) $row['providerId'], $result);
    }
}




