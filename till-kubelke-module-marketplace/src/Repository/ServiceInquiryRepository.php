<?php

namespace TillKubelke\ModuleMarketplace\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use TillKubelke\ModuleMarketplace\Entity\ServiceInquiry;
use TillKubelke\ModuleMarketplace\Entity\ServiceProvider;
use TillKubelke\PlatformFoundation\Tenant\Entity\Tenant;

/**
 * @extends ServiceEntityRepository<ServiceInquiry>
 */
class ServiceInquiryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ServiceInquiry::class);
    }

    /**
     * Find inquiries for a tenant.
     *
     * @return ServiceInquiry[]
     */
    public function findByTenant(Tenant $tenant, int $limit = 50, int $offset = 0): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.tenant = :tenant')
            ->setParameter('tenant', $tenant)
            ->orderBy('i.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find inquiries for a provider.
     *
     * @return ServiceInquiry[]
     */
    public function findByProvider(ServiceProvider $provider, ?string $status = null, int $limit = 50, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('i')
            ->where('i.provider = :provider')
            ->setParameter('provider', $provider)
            ->orderBy('i.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        if ($status !== null) {
            $qb->andWhere('i.status = :status')
                ->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Count new inquiries for a provider.
     */
    public function countNewByProvider(ServiceProvider $provider): int
    {
        return (int) $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->where('i.provider = :provider')
            ->andWhere('i.status = :status')
            ->setParameter('provider', $provider)
            ->setParameter('status', ServiceInquiry::STATUS_NEW)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find inquiries linked to a BGM project.
     *
     * @return ServiceInquiry[]
     */
    public function findByBgmProject(int $bgmProjectId): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.bgmProjectId = :projectId')
            ->setParameter('projectId', $bgmProjectId)
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(ServiceInquiry $inquiry, bool $flush = false): void
    {
        $this->getEntityManager()->persist($inquiry);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}



