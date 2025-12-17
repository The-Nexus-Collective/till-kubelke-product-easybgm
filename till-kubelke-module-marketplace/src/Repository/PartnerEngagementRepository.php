<?php

namespace TillKubelke\ModuleMarketplace\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use TillKubelke\ModuleMarketplace\Entity\PartnerEngagement;
use TillKubelke\ModuleMarketplace\Entity\ServiceProvider;
use TillKubelke\PlatformFoundation\Tenant\Entity\Tenant;

/**
 * @extends ServiceEntityRepository<PartnerEngagement>
 */
class PartnerEngagementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PartnerEngagement::class);
    }

    /**
     * Find all engagements for a tenant.
     * 
     * @return PartnerEngagement[]
     */
    public function findByTenant(Tenant $tenant): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.tenant = :tenant')
            ->setParameter('tenant', $tenant)
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find active (ongoing) engagements for a tenant.
     * 
     * @return PartnerEngagement[]
     */
    public function findActiveByTenant(Tenant $tenant): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.tenant = :tenant')
            ->andWhere('e.status NOT IN (:endStatuses)')
            ->setParameter('tenant', $tenant)
            ->setParameter('endStatuses', [
                PartnerEngagement::STATUS_COMPLETED,
                PartnerEngagement::STATUS_CANCELLED,
            ])
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find engagements for a provider.
     * 
     * @return PartnerEngagement[]
     */
    public function findByProvider(ServiceProvider $provider): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.provider = :provider')
            ->setParameter('provider', $provider)
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find active engagements for a provider.
     * 
     * @return PartnerEngagement[]
     */
    public function findActiveByProvider(ServiceProvider $provider): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.provider = :provider')
            ->andWhere('e.status NOT IN (:endStatuses)')
            ->setParameter('provider', $provider)
            ->setParameter('endStatuses', [
                PartnerEngagement::STATUS_COMPLETED,
                PartnerEngagement::STATUS_CANCELLED,
            ])
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find engagements by status.
     * 
     * @return PartnerEngagement[]
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.status = :status')
            ->setParameter('status', $status)
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find engagements for a tenant with a specific status.
     * 
     * @return PartnerEngagement[]
     */
    public function findByTenantAndStatus(Tenant $tenant, string $status): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.tenant = :tenant')
            ->andWhere('e.status = :status')
            ->setParameter('tenant', $tenant)
            ->setParameter('status', $status)
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count active engagements for a tenant.
     */
    public function countActiveByTenant(Tenant $tenant): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->andWhere('e.tenant = :tenant')
            ->andWhere('e.status NOT IN (:endStatuses)')
            ->setParameter('tenant', $tenant)
            ->setParameter('endStatuses', [
                PartnerEngagement::STATUS_COMPLETED,
                PartnerEngagement::STATUS_CANCELLED,
            ])
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find engagements that have unintegrated deliverables.
     * 
     * @return PartnerEngagement[]
     */
    public function findWithPendingIntegration(Tenant $tenant): array
    {
        // Find engagements that are delivered but not fully integrated
        return $this->createQueryBuilder('e')
            ->andWhere('e.tenant = :tenant')
            ->andWhere('e.status = :status')
            ->andWhere('e.deliveredOutputs IS NOT NULL')
            ->setParameter('tenant', $tenant)
            ->setParameter('status', PartnerEngagement::STATUS_DELIVERED)
            ->orderBy('e.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
